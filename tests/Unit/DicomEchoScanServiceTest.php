<?php

namespace Tests\Unit;

use App\Services\Discovery\DicomEchoScanService;
use App\Services\Discovery\DiscoveryEchoCommandResult;
use App\Services\Discovery\DiscoveryEchoCommandRunner;
use App\Services\Discovery\DiscoveryEchoTarget;
use PHPUnit\Framework\TestCase;

final class DicomEchoScanServiceTest extends TestCase
{
    public function test_ae_title_attempts_are_bounded_per_host_and_port(): void
    {
        $runner = new class implements DiscoveryEchoCommandRunner
        {
            /** @var list<DiscoveryEchoTarget> */
            public array $received = [];

            public function runMany(array $targets, int $timeoutSeconds): array
            {
                $this->received = $targets;

                $results = [];
                foreach ($targets as $target) {
                    $results[$target->key()] = new DiscoveryEchoCommandResult($target, true, true, null, null, '', 10);
                }

                return $results;
            }
        };

        $service = new DicomEchoScanService($runner, maxAttemptsPerPort: 2);

        $targets = [
            new DiscoveryEchoTarget('192.168.20.10', 11112, 'HNR_DISCOVERY', 'PACS01'),
            new DiscoveryEchoTarget('192.168.20.10', 11112, 'HNR_DISCOVERY', 'PACS02'),
            new DiscoveryEchoTarget('192.168.20.10', 11112, 'HNR_DISCOVERY', 'PACS03'),
            new DiscoveryEchoTarget('192.168.20.10', 104, 'HNR_DISCOVERY', 'MWL01'),
        ];

        $service->testMany($targets, 5);

        // Port 11112: nur 2 der 3 AE-Titel-Kandidaten dürfen versucht werden.
        self::assertCount(3, $runner->received);
        $portCounts = [];
        foreach ($runner->received as $target) {
            $portCounts[$target->port] = ($portCounts[$target->port] ?? 0) + 1;
        }
        self::assertSame(2, $portCounts[11112]);
        self::assertSame(1, $portCounts[104]);
    }

    public function test_no_targets_yields_no_runner_calls(): void
    {
        $runner = new class implements DiscoveryEchoCommandRunner
        {
            public int $calls = 0;

            public function runMany(array $targets, int $timeoutSeconds): array
            {
                $this->calls++;

                return [];
            }
        };

        $service = new DicomEchoScanService($runner, maxAttemptsPerPort: 5);

        $service->testMany([], 5);

        self::assertSame(0, $runner->calls);
    }

    public function test_failed_echoes_are_retried_without_repeating_successful_targets(): void
    {
        $runner = new class implements DiscoveryEchoCommandRunner
        {
            /** @var list<list<DiscoveryEchoTarget>> */
            public array $batches = [];

            public function runMany(array $targets, int $timeoutSeconds): array
            {
                $this->batches[] = $targets;
                $attempt = count($this->batches);
                $results = [];

                foreach ($targets as $target) {
                    $successful = $target->calledAeTitle === 'PACS_OK' || $attempt === 2;
                    $results[$target->key()] = new DiscoveryEchoCommandResult(
                        $target,
                        $successful,
                        $successful,
                        $successful ? null : 'timeout',
                        $successful ? null : 'Zeitüberschreitung',
                        '',
                        10,
                    );
                }

                return $results;
            }
        };
        $failedThenSuccessful = new DiscoveryEchoTarget('192.168.20.10', 11112, 'HNR_DISCOVERY', 'PACS_RETRY');
        $successful = new DiscoveryEchoTarget('192.168.20.10', 11112, 'HNR_DISCOVERY', 'PACS_OK');

        $results = (new DicomEchoScanService($runner, maxAttemptsPerPort: 5))
            ->testMany([$failedThenSuccessful, $successful], 5, 2);

        self::assertCount(2, $runner->batches);
        self::assertSame([$failedThenSuccessful], $runner->batches[1]);
        self::assertTrue($results[$failedThenSuccessful->key()]->echoSuccessful);
        self::assertTrue($results[$successful->key()]->echoSuccessful);
    }
}
