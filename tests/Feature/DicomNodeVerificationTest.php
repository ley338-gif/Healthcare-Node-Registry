<?php

namespace Tests\Feature;

use App\Models\DicomNode;
use App\Models\Role;
use App\Models\User;
use App\Services\Dicom\DicomEchoResult;
use App\Services\Dicom\DicomEchoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

final class DicomNodeVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_manager_can_successfully_verify_a_dicom_node(): void
    {
        $user = $this->createRegistryManager();

        $dicomNode = DicomNode::factory()->create([
            'supports_echo' => true,
            'last_verified_at' => null,
            'last_verification_status' => null,
        ]);

        $this->mock(
            DicomEchoService::class,
            function (MockInterface $mock) use ($dicomNode): void {
                $mock
                    ->shouldReceive('test')
                    ->once()
                    ->withArgs(
                        fn (DicomNode $node): bool => $node->is($dicomNode),
                    )
                    ->andReturn(
                        new DicomEchoResult(
                            successful: true,
                            status: 'success',
                            durationMilliseconds: 32,
                            message: 'C-ECHO erfolgreich.',
                            exitCode: 0,
                        ),
                    );
            },
        );

        $response = $this
            ->actingAs($user)
            ->post(
                "/dicom-nodes/{$dicomNode->public_id}/verify",
            );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $dicomNode->refresh();

        $this->assertNotNull($dicomNode->last_verified_at);

        $this->assertSame(
            'success',
            $dicomNode->last_verification_status,
        );

        $this->assertSame(
            32,
            $dicomNode->last_verification_duration_ms,
        );

        $this->assertSame(
            'C-ECHO erfolgreich.',
            $dicomNode->last_verification_message,
        );

        $this->assertDatabaseHas('security_events', [
            'event_type' => 'registry.dicom_node.verified',
            'subject_type' => DicomNode::class,
            'subject_public_id' => $dicomNode->public_id,
        ]);
    }

    public function test_failed_verification_is_saved(): void
    {
        $user = $this->createRegistryManager();

        $dicomNode = DicomNode::factory()->create([
            'supports_echo' => true,
        ]);

        $this->mock(
            DicomEchoService::class,
            function (MockInterface $mock): void {
                $mock
                    ->shouldReceive('test')
                    ->once()
                    ->andReturn(
                        new DicomEchoResult(
                            successful: false,
                            status: 'timeout',
                            durationMilliseconds: 5000,
                            message: 'Connection timed out.',
                            exitCode: 1,
                        ),
                    );
            },
        );

        $response = $this
            ->actingAs($user)
            ->post(
                "/dicom-nodes/{$dicomNode->public_id}/verify",
            );

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $dicomNode->refresh();

        $this->assertNotNull($dicomNode->last_verified_at);

        $this->assertSame(
            'timeout',
            $dicomNode->last_verification_status,
        );

        $this->assertSame(
            'Connection timed out.',
            $dicomNode->last_verification_message,
        );

        $this->assertSame(
            5000,
            $dicomNode->last_verification_duration_ms,
        );
    }

    public function test_node_without_echo_support_cannot_be_verified(): void
    {
        $user = $this->createRegistryManager();

        $dicomNode = DicomNode::factory()->create([
            'supports_echo' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                "/dicom-nodes/{$dicomNode->public_id}/verify",
            );

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $dicomNode->refresh();

        $this->assertNull($dicomNode->last_verified_at);
    }

    public function test_unprivileged_user_cannot_verify_a_dicom_node(): void
    {
        $user = User::factory()->create();

        $dicomNode = DicomNode::factory()->create([
            'supports_echo' => true,
        ]);

        $this
            ->actingAs($user)
            ->post(
                "/dicom-nodes/{$dicomNode->public_id}/verify",
            )
            ->assertForbidden();
    }

    private function createRegistryManager(): User
    {
        $this->seed();

        $user = User::factory()->create();

        $role = Role::query()
            ->where('name', 'system-administrator')
            ->firstOrFail();

        $user->roles()->attach($role);

        return $user;
    }
}
