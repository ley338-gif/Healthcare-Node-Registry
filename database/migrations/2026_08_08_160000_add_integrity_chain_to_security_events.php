<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const GENESIS_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

    public function up(): void
    {
        Schema::table('security_events', function (Blueprint $table): void {
            $table->char('previous_hash', 64)->nullable();
            $table->char('event_hash', 64)->nullable()->unique();
            $table->timestampTz('retention_until')->nullable()->index();
        });

        $previousHash = self::GENESIS_HASH;
        $lastEventId = null;
        $retentionDays = max(1, (int) config('audit.retention_days', 7 * 365));

        DB::table('security_events')->orderBy('id')->each(function (object $event) use (&$previousHash, &$lastEventId, $retentionDays): void {
            $occurredAt = CarbonImmutable::parse((string) $event->occurred_at)->utc();
            $retentionUntil = $occurredAt->addDays($retentionDays);
            $metadata = is_string($event->metadata)
                ? json_decode($event->metadata, true, flags: JSON_THROW_ON_ERROR)
                : (array) $event->metadata;
            $eventHash = hash('sha256', $this->canonicalJson([
                'actor_type' => (string) $event->actor_type,
                'event_id' => (string) $event->event_id,
                'event_type' => (string) $event->event_type,
                'metadata' => $metadata,
                'occurred_at' => $occurredAt->format('Y-m-d\TH:i:s.u\Z'),
                'previous_hash' => $previousHash,
                'retention_until' => $retentionUntil->format('Y-m-d\TH:i:s.u\Z'),
                'subject_public_id' => $event->subject_public_id === null ? null : (string) $event->subject_public_id,
                'subject_type' => $event->subject_type === null ? null : (string) $event->subject_type,
            ]));

            DB::table('security_events')->where('id', $event->id)->update([
                'previous_hash' => $previousHash,
                'event_hash' => $eventHash,
                'retention_until' => $retentionUntil,
            ]);
            $previousHash = $eventHash;
            $lastEventId = (int) $event->id;
        });

        DB::statement('ALTER TABLE security_events ALTER COLUMN previous_hash SET NOT NULL');
        DB::statement('ALTER TABLE security_events ALTER COLUMN event_hash SET NOT NULL');
        DB::statement('ALTER TABLE security_events ALTER COLUMN retention_until SET NOT NULL');

        Schema::create('audit_chain_state', function (Blueprint $table): void {
            $table->unsignedSmallInteger('id')->primary();
            $table->unsignedBigInteger('last_event_id')->nullable();
            $table->char('last_event_hash', 64);
            $table->unsignedBigInteger('pruned_through_event_id')->nullable();
            $table->char('pruned_through_hash', 64);
            $table->timestampTz('pruned_through_occurred_at')->nullable();
            $table->timestampTz('updated_at');
        });

        DB::table('audit_chain_state')->insert([
            'id' => 1,
            'last_event_id' => $lastEventId,
            'last_event_hash' => $previousHash,
            'pruned_through_event_id' => null,
            'pruned_through_hash' => self::GENESIS_HASH,
            'pruned_through_occurred_at' => null,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_chain_state');
        Schema::table('security_events', function (Blueprint $table): void {
            $table->dropUnique(['event_hash']);
            $table->dropIndex(['retention_until']);
            $table->dropColumn(['previous_hash', 'event_hash', 'retention_until']);
        });
    }

    /** @param array<string, mixed> $value */
    private function canonicalJson(array $value): string
    {
        return json_encode($this->canonicalize($value), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
        }
        ksort($value, SORT_STRING);

        return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
    }
};
