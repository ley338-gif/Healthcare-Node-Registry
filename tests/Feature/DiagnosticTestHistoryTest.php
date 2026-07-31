<?php

namespace Tests\Feature;

use App\Models\DiagnosticTestRun;
use App\Models\DicomNode;
use App\Models\Role;
use App\Models\User;
use App\Services\Diagnostics\DiagnosticTarget;
use App\Services\Diagnostics\DiagnosticTestRecorder;
use App\Services\Diagnostics\DiagnosticTestResult;
use App\Services\Diagnostics\DiagnosticTestStatus;
use App\Services\Diagnostics\DiagnosticTestStep;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class DiagnosticTestHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_recorder_persists_sanitized_result(): void
    {
        $node = DicomNode::factory()->create();
        $user = User::factory()->create();
        $result = new DiagnosticTestResult(
            testId: '0198f08e-8b10-7000-8000-000000000001',
            testType: 'network',
            status: DiagnosticTestStatus::Failed,
            startedAt: new DateTimeImmutable('-1 second'),
            finishedAt: new DateTimeImmutable,
            durationMilliseconds: 1000,
            summary: 'Verbindung fehlgeschlagen.',
            target: new DiagnosticTarget($node->host, $node->port),
            steps: [
                new DiagnosticTestStep(
                    'tcp_connection',
                    'TCP-Verbindung',
                    DiagnosticTestStatus::Failed,
                    1000,
                    'Fehlgeschlagen.',
                    ['password' => 'step-secret'],
                ),
            ],
            details: [
                'password' => 'top-secret',
                'nested' => ['authorizationToken' => 'bearer-secret'],
                'log' => 'Failure in C:\\internal\\diagnostics\\runner.php',
            ],
            errors: ['Verbindung fehlgeschlagen.'],
        );

        $run = (new DiagnosticTestRecorder)->record($result, $node, $user);

        self::assertSame('[REDACTED]', $run->details['password']);
        self::assertSame('[REDACTED]', $run->details['nested']['authorizationToken']);
        self::assertStringNotContainsString('runner.php', $run->details['log']);
        self::assertSame('[REDACTED]', $run->steps[0]['details']['password']);
        self::assertNull($run->sanitized_log_excerpt);
    }

    public function test_history_is_paginated(): void
    {
        $user = $this->createRegistryManager();
        $node = DicomNode::factory()->create();
        DiagnosticTestRun::factory()->count(12)->create([
            'dicom_node_id' => $node->id,
            'system_id' => $node->system_id,
        ]);

        $this->actingAs($user)->get('/tests')->assertInertia(
            fn (Assert $page) => $page
                ->component('Tests/Index')
                ->has('history.data', 10)
                ->where('history.total', 12),
        );
    }

    public function test_history_filters_by_node_type_status_user_and_date(): void
    {
        $viewer = $this->createRegistryManager();
        $actor = User::factory()->create();
        $matchingNode = DicomNode::factory()->create();
        $otherNode = DicomNode::factory()->create();

        DiagnosticTestRun::factory()->create([
            'user_id' => $actor->id,
            'dicom_node_id' => $matchingNode->id,
            'system_id' => $matchingNode->system_id,
            'test_type' => 'network',
            'status' => 'timeout',
            'started_at' => '2026-07-30 12:00:00+00',
        ]);
        DiagnosticTestRun::factory()->create([
            'dicom_node_id' => $otherNode->id,
            'system_id' => $otherNode->system_id,
            'test_type' => 'dicom_echo',
            'status' => 'success',
            'started_at' => '2026-07-20 12:00:00+00',
        ]);

        $query = http_build_query([
            'history_from' => '2026-07-30',
            'history_to' => '2026-07-30',
            'history_node' => $matchingNode->public_id,
            'history_type' => 'network',
            'history_status' => 'timeout',
            'history_user' => $actor->public_id,
        ]);

        $this->actingAs($viewer)->get("/tests?{$query}")->assertInertia(
            fn (Assert $page) => $page
                ->has('history.data', 1)
                ->where('history.data.0.dicom_node.public_id', $matchingNode->public_id)
                ->where('history.data.0.status', 'timeout'),
        );
    }

    public function test_unprivileged_user_cannot_view_history_details(): void
    {
        DiagnosticTestRun::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get('/tests')
            ->assertForbidden();
    }

    private function createRegistryManager(): User
    {
        $this->seed();
        $user = User::factory()->create();
        $role = Role::query()->where('name', 'system-administrator')->firstOrFail();
        $user->roles()->attach($role);

        return $user;
    }
}
