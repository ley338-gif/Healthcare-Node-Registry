<?php

namespace Tests\Unit;

use App\Services\Diagnostics\DiagnosticTarget;
use App\Services\Diagnostics\DiagnosticTestResult;
use App\Services\Diagnostics\DiagnosticTestStatus;
use App\Services\Diagnostics\DiagnosticTestStep;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class DiagnosticTestResultTest extends TestCase
{
    public function test_it_serializes_a_standardized_diagnostic_result(): void
    {
        $startedAt = new DateTimeImmutable('2026-07-31T10:00:00+00:00');
        $finishedAt = new DateTimeImmutable('2026-07-31T10:00:00.125+00:00');

        $result = new DiagnosticTestResult(
            testId: 'test-123',
            testType: 'network',
            status: DiagnosticTestStatus::Success,
            startedAt: $startedAt,
            finishedAt: $finishedAt,
            durationMilliseconds: 125,
            summary: 'Verbindung erfolgreich.',
            target: new DiagnosticTarget(
                host: 'pacs.example.test',
                port: 104,
                calledAeTitle: 'PACS',
                callingAeTitle: 'NODE_REGISTRY',
                dicomNodePublicId: 'node-123',
                systemPublicId: 'system-123',
            ),
            steps: [
                new DiagnosticTestStep(
                    name: 'tcp_connection',
                    label: 'TCP-Verbindung',
                    status: DiagnosticTestStatus::Success,
                    durationMilliseconds: 125,
                    message: 'Port erreichbar.',
                    details: ['resolvedAddress' => '192.0.2.10'],
                ),
            ],
            details: ['protocol' => 'tcp'],
        );

        $serialized = $result->toArray();

        self::assertSame('test-123', $serialized['testId']);
        self::assertSame('success', $serialized['status']);
        self::assertSame('pacs.example.test', $serialized['target']['host']);
        self::assertSame('tcp_connection', $serialized['steps'][0]['name']);
        self::assertSame([], $serialized['warnings']);
        self::assertSame([], $serialized['errors']);
    }

    public function test_all_supported_status_values_are_stable(): void
    {
        self::assertSame(
            ['success', 'warning', 'failed', 'timeout', 'cancelled', 'unsupported'],
            array_column(DiagnosticTestStatus::cases(), 'value'),
        );
    }
}
