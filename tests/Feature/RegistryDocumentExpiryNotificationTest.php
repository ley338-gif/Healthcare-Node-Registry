<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\RegistryDocument;
use App\Models\Role;
use App\Models\User;
use App\Notifications\RegistryDocumentExpiryNotification;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RegistryDocumentExpiryNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow('2026-08-08 08:00:00');
        config()->set('registry_documents.expiry_warning_days', 60);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_command_notifies_authorized_active_users_once_per_document_deadline(): void
    {
        $recipient = $this->documentsUser();
        $inactiveRecipient = $this->documentsUser(false);
        $unauthorizedUser = User::factory()->create();
        $expiring = RegistryDocument::factory()->create(['title' => 'TLS-Zertifikat', 'valid_until' => '2026-08-20']);
        $expired = RegistryDocument::factory()->create(['title' => 'Wartungsvertrag', 'valid_until' => '2026-08-01']);
        RegistryDocument::factory()->create(['valid_until' => '2026-11-01']);
        RegistryDocument::factory()->create(['valid_until' => null]);
        RegistryDocument::factory()->create(['valid_until' => '2026-08-10', 'status' => 'archived', 'archived_at' => now()]);

        $this->artisan('registry-documents:notify-expiry')->assertSuccessful();

        self::assertCount(2, $recipient->fresh()->notifications);
        self::assertCount(0, $inactiveRecipient->fresh()->notifications);
        self::assertCount(0, $unauthorizedUser->fresh()->notifications);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $recipient->id,
            'type' => RegistryDocumentExpiryNotification::class,
        ]);
        self::assertSame(
            collect([$expired->public_id, $expiring->public_id])->sort()->values()->all(),
            $recipient->fresh()->notifications->pluck('data.document_public_id')->sort()->values()->all(),
        );

        $this->artisan('registry-documents:notify-expiry')->assertSuccessful();

        self::assertCount(2, $recipient->fresh()->notifications);
    }

    public function test_dashboard_shows_current_expiry_widget_for_document_users(): void
    {
        $this->withoutVite();
        $recipient = $this->documentsUser();
        RegistryDocument::factory()->create(['title' => 'PACS-Lizenz', 'valid_until' => '2026-08-18']);
        RegistryDocument::factory()->create(['title' => 'Abgelaufenes Zertifikat', 'valid_until' => '2026-08-07']);
        $this->artisan('registry-documents:notify-expiry')->assertSuccessful();

        $this->actingAs($recipient)->get('/')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('expiringDocuments.total', 2)
            ->where('expiringDocuments.expired', 1)
            ->where('expiringDocuments.expiringSoon', 1)
            ->where('expiringDocuments.warningDays', 60)
            ->has('expiringDocuments.items', 2)
            ->where('expiringDocuments.items.0.title', 'Abgelaufenes Zertifikat')
            ->where('expiringDocuments.items.0.status', 'expired')
            ->where('expiringDocuments.items.0.unread', true));

        $this->actingAs(User::factory()->create())->get('/')->assertInertia(fn ($page) => $page
            ->where('expiringDocuments', null));
    }

    public function test_opening_expiry_notification_marks_it_as_read_and_opens_document(): void
    {
        $recipient = $this->documentsUser();
        $document = RegistryDocument::factory()->create(['valid_until' => '2026-08-18']);
        $this->artisan('registry-documents:notify-expiry')->assertSuccessful();
        $notification = $recipient->fresh()->unreadNotifications->firstOrFail();

        $this->actingAs($recipient)
            ->get(route('notifications.show', ['notification' => $notification->id]))
            ->assertRedirect(route('documents.index', ['document' => $document->public_id]));

        self::assertNotNull($notification->fresh()->read_at);
    }

    private function documentsUser(bool $active = true): User
    {
        $permission = Permission::query()->firstOrCreate(
            ['name' => 'documents.view'],
            ['display_name' => 'Dokumente anzeigen'],
        );
        $role = Role::query()->firstOrCreate(
            ['name' => 'document-test-reader'],
            ['display_name' => 'Dokument-Testleser'],
        );
        $role->permissions()->syncWithoutDetaching([$permission->id]);
        $user = User::factory()->create(['is_active' => $active]);
        $user->roles()->attach($role);

        return $user;
    }
}
