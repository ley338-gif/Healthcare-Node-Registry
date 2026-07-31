<?php

namespace App\Services\Diagnostics;

use App\Models\DicomNode;
use DateTimeImmutable;
use Illuminate\Support\Str;

final class NetworkConnectionTest
{
    private readonly NetworkProbe $probe;

    public function __construct(?NetworkProbe $probe = null)
    {
        $this->probe = $probe ?? new NativeNetworkProbe;
    }

    public function run(DicomNode $dicomNode): DiagnosticTestResult
    {
        $startedAt = new DateTimeImmutable;
        $startedAtNanoseconds = hrtime(true);
        $target = $this->target($dicomNode);

        if (! $this->isValidHost($target->host) || $target->port < 1 || $target->port > 65535) {
            return $this->failedResult(
                $startedAt,
                $startedAtNanoseconds,
                $target,
                'Zielvalidierung',
                'Der registrierte Knoten enthält einen ungültigen Host oder Port.',
            );
        }

        $dnsStartedAt = hrtime(true);
        $addresses = $this->probe->resolve($target->host);
        $dnsDuration = $this->elapsedMilliseconds($dnsStartedAt);

        if ($addresses === []) {
            return $this->failedResult(
                $startedAt,
                $startedAtNanoseconds,
                $target,
                'DNS-Auflösung',
                'DNS-Auflösung fehlgeschlagen.',
                [
                    new DiagnosticTestStep(
                        'dns_resolution',
                        'DNS-Auflösung',
                        DiagnosticTestStatus::Failed,
                        $dnsDuration,
                        'Für den registrierten Host wurde keine Adresse gefunden.',
                    ),
                ],
            );
        }

        $timeout = (float) config('diagnostics.network_timeout_seconds', 5.0);
        $connectionStartedAt = hrtime(true);
        $connection = $this->probe->connect($addresses[0], $target->port, $timeout);
        $connectionDuration = $this->elapsedMilliseconds($connectionStartedAt);
        $dnsStep = new DiagnosticTestStep(
            'dns_resolution',
            'DNS-Auflösung',
            DiagnosticTestStatus::Success,
            $dnsDuration,
            'Host wurde erfolgreich aufgelöst.',
            ['resolvedAddresses' => $addresses],
        );

        if (! $connection->connected) {
            $status = $this->failureStatus($connection);
            $message = $this->failureMessage($connection, $status);

            return $this->result(
                $startedAt,
                $startedAtNanoseconds,
                $target,
                $status,
                $message,
                [
                    $dnsStep,
                    new DiagnosticTestStep(
                        'tcp_connection',
                        'TCP-Verbindung',
                        $status,
                        $connectionDuration,
                        $message,
                        ['errorCode' => $connection->errorCode],
                    ),
                ],
                errors: [$message],
            );
        }

        return $this->result(
            $startedAt,
            $startedAtNanoseconds,
            $target,
            DiagnosticTestStatus::Success,
            'Netzwerkverbindung erfolgreich.',
            [
                $dnsStep,
                new DiagnosticTestStep(
                    'tcp_connection',
                    'TCP-Verbindung',
                    DiagnosticTestStatus::Success,
                    $connectionDuration,
                    'Der registrierte TCP-Port ist erreichbar.',
                ),
            ],
        );
    }

    private function target(DicomNode $dicomNode): DiagnosticTarget
    {
        return new DiagnosticTarget(
            host: $dicomNode->host,
            port: $dicomNode->port,
            calledAeTitle: $dicomNode->ae_title,
            dicomNodePublicId: $dicomNode->public_id,
            systemPublicId: $dicomNode->system->public_id,
        );
    }

    private function isValidHost(string $host): bool
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return true;
        }

        return strlen($host) <= 253
            && filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false;
    }

    private function failureStatus(NetworkProbeResult $result): DiagnosticTestStatus
    {
        $error = strtolower($result->errorMessage);

        return str_contains($error, 'timed out') || str_contains($error, 'timeout')
            ? DiagnosticTestStatus::Timeout
            : DiagnosticTestStatus::Failed;
    }

    private function failureMessage(
        NetworkProbeResult $result,
        DiagnosticTestStatus $status,
    ): string {
        if ($status === DiagnosticTestStatus::Timeout) {
            return 'Zeitüberschreitung beim Aufbau der TCP-Verbindung.';
        }

        if (str_contains(strtolower($result->errorMessage), 'refused')) {
            return 'TCP-Verbindung wurde vom Ziel abgelehnt.';
        }

        return 'Der registrierte Host oder Port ist nicht erreichbar.';
    }

    /** @param list<DiagnosticTestStep> $steps */
    private function failedResult(
        DateTimeImmutable $startedAt,
        int $startedAtNanoseconds,
        DiagnosticTarget $target,
        string $stepLabel,
        string $message,
        array $steps = [],
    ): DiagnosticTestResult {
        if ($steps === []) {
            $steps[] = new DiagnosticTestStep(
                'target_validation',
                $stepLabel,
                DiagnosticTestStatus::Failed,
                0,
                $message,
            );
        }

        return $this->result(
            $startedAt,
            $startedAtNanoseconds,
            $target,
            DiagnosticTestStatus::Failed,
            $message,
            $steps,
            errors: [$message],
        );
    }

    /**
     * @param  list<DiagnosticTestStep>  $steps
     * @param  list<string>  $errors
     */
    private function result(
        DateTimeImmutable $startedAt,
        int $startedAtNanoseconds,
        DiagnosticTarget $target,
        DiagnosticTestStatus $status,
        string $summary,
        array $steps,
        array $errors = [],
    ): DiagnosticTestResult {
        return new DiagnosticTestResult(
            testId: (string) Str::uuid7(),
            testType: 'network',
            status: $status,
            startedAt: $startedAt,
            finishedAt: new DateTimeImmutable,
            durationMilliseconds: $this->elapsedMilliseconds($startedAtNanoseconds),
            summary: $summary,
            target: $target,
            steps: $steps,
            errors: $errors,
        );
    }

    private function elapsedMilliseconds(int $startedAt): int
    {
        return (int) round((hrtime(true) - $startedAt) / 1_000_000);
    }
}
