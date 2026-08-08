<?php

namespace Tests\Feature;

use App\Models\SecurityEvent;
use App\Models\System;
use App\Models\User;
use App\Services\Audit\AuditChainHasher;
use App\Support\RegistryAudit;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

final class AuditChainIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_events_form_a_verifiable_hash_chain(): void
    {
        $events = $this->recordEvents(3);

        self::assertSame(AuditChainHasher::GENESIS_HASH, $events[0]->previous_hash);
        self::assertSame($events[0]->event_hash, $events[1]->previous_hash);
        self::assertSame($events[1]->event_hash, $events[2]->previous_hash);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $events[2]->event_hash);
        self::assertTrue($events[0]->retention_until->isAfter($events[0]->occurred_at));

        $this->artisan('registry:audit-verify')
            ->expectsOutputToContain('Audit-Kette gültig: 3 Einträge geprüft.')
            ->assertSuccessful();
    }

    public function test_verify_command_detects_a_modified_event(): void
    {
        $events = $this->recordEvents(3);
        DB::table('security_events')->where('id', $events[1]->id)->update([
            'event_type' => 'registry.system.tampered',
        ]);

        $this->artisan('registry:audit-verify')
            ->expectsOutputToContain('Audit-Kette ungültig')
            ->expectsOutputToContain('Datenbank-ID: '.$events[1]->id)
            ->expectsOutputToContain('Event-ID: '.$events[1]->event_id)
            ->assertFailed();
    }

    public function test_verify_command_detects_an_event_deleted_from_the_middle(): void
    {
        $events = $this->recordEvents(3);
        DB::table('security_events')->where('id', $events[1]->id)->delete();

        $this->artisan('registry:audit-verify')
            ->expectsOutputToContain('Audit-Kette ungültig')
            ->expectsOutputToContain('Datenbank-ID: '.$events[2]->id)
            ->assertFailed();
    }

    public function test_verify_command_detects_a_deleted_chain_tail(): void
    {
        $events = $this->recordEvents(2);
        DB::table('security_events')->where('id', $events[1]->id)->delete();

        $this->artisan('registry:audit-verify')
            ->expectsOutputToContain('Kettenkopf stimmt nicht')
            ->assertFailed();
    }

    public function test_eloquent_cannot_update_or_delete_audit_events(): void
    {
        $event = $this->recordEvents(1)[0];

        try {
            $event->update(['event_type' => 'registry.system.tampered']);
            self::fail('Updating an audit event must fail.');
        } catch (LogicException $exception) {
            self::assertSame('Audit events are immutable.', $exception->getMessage());
        }

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('controlled retention process');
        $event->delete();
    }

    public function test_pruner_removes_only_an_expired_prefix_and_preserves_verification(): void
    {
        config()->set('audit.retention_days', 30);
        CarbonImmutable::setTestNow('2026-01-01 00:00:00 UTC');
        $expired = $this->recordEvents(2);
        CarbonImmutable::setTestNow('2026-03-01 00:00:00 UTC');
        $retained = $this->recordEvents(1)[0];

        $this->artisan('registry:audit-prune')
            ->expectsOutputToContain('2 abgelaufene Audit-Einträge kontrolliert entfernt.')
            ->assertSuccessful();

        self::assertSame([$retained->id], SecurityEvent::query()->pluck('id')->all());
        $state = DB::table('audit_chain_state')->where('id', 1)->firstOrFail();
        self::assertSame($expired[1]->id, (int) $state->pruned_through_event_id);
        self::assertSame($expired[1]->event_hash, $state->pruned_through_hash);
        $this->artisan('registry:audit-verify')
            ->expectsOutputToContain('Audit-Kette gültig: 1 Einträge geprüft.')
            ->assertSuccessful();
    }

    public function test_pruner_does_not_remove_events_before_their_retention_deadline(): void
    {
        config()->set('audit.retention_days', 30);
        CarbonImmutable::setTestNow('2026-03-01 00:00:00 UTC');
        $event = $this->recordEvents(1)[0];

        $this->artisan('registry:audit-prune')->assertSuccessful();

        self::assertTrue(SecurityEvent::query()->whereKey($event->id)->exists());
    }

    public function test_pruner_refuses_to_checkpoint_a_broken_expired_chain(): void
    {
        config()->set('audit.retention_days', 30);
        CarbonImmutable::setTestNow('2026-01-01 00:00:00 UTC');
        $events = $this->recordEvents(3);
        DB::table('security_events')->where('id', $events[1]->id)->delete();
        CarbonImmutable::setTestNow('2026-03-01 00:00:00 UTC');

        $this->artisan('registry:audit-prune')
            ->expectsOutputToContain('Audit-Kette ungültig; es wurden keine Einträge entfernt')
            ->assertFailed();

        self::assertSame(
            [$events[0]->id, $events[2]->id],
            SecurityEvent::query()->orderBy('id')->pluck('id')->all(),
        );
    }

    /** @return list<SecurityEvent> */
    private function recordEvents(int $count): array
    {
        $audit = new RegistryAudit;
        $actor = User::factory()->create();
        $system = System::factory()->create();
        $events = [];

        for ($number = 1; $number <= $count; $number++) {
            $audit->record('registry.system.updated', $system, $actor, [
                'sequence' => $number,
                'nested' => ['z' => true, 'a' => 'canonical'],
            ]);
            $events[] = SecurityEvent::query()->latest('id')->firstOrFail();
        }

        return $events;
    }
}
