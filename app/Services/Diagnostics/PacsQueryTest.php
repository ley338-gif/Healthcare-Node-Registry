<?php

namespace App\Services\Diagnostics;

use App\Models\DicomNode;
use DateTimeImmutable;
use Illuminate\Support\Str;
use Throwable;

final class PacsQueryTest
{
    private readonly PacsQueryCommandRunner $runner;

    public function __construct(?PacsQueryCommandRunner $runner = null, private readonly PacsQueryResponseParser $parser = new PacsQueryResponseParser)
    {
        $this->runner = $runner ?? new NativePacsQueryCommandRunner;
    }

    public function run(DicomNode $node, PacsQueryParameters $parameters): DiagnosticTestResult
    {
        $started = new DateTimeImmutable;
        $timer = hrtime(true);
        $command = $this->runner->run($node, $parameters);
        $duration = (int) round((hrtime(true) - $timer) / 1_000_000);
        $normalized = strtolower($command->output);
        $failureType = match (true) {
            $command->timedOut, str_contains($normalized, 'timeout'), str_contains($normalized, 'timed out') => 'timeout',
            str_contains($normalized, 'association rejected') => 'association_rejected',
            $command->processError => 'process_error', default => 'c_find_failed',
        };

        if (! $command->successful) {
            $status = $failureType === 'timeout' ? DiagnosticTestStatus::Timeout : DiagnosticTestStatus::Failed;
            $summary = match ($failureType) {
                'timeout' => 'Zeitüberschreitung bei der PACS-Abfrage.',
                'association_rejected' => 'DICOM Association wurde abgelehnt.',
                'process_error' => 'DCMTK-Prozess konnte nicht ausgeführt werden.',
                default => 'PACS C-FIND ist technisch fehlgeschlagen.',
            };

            return $this->result($node, $parameters, $started, $duration, $status, $summary, [], [
                'exitCode' => $command->exitCode, 'failureType' => $failureType, 'resultCount' => 0,
            ], [$summary]);
        }

        try {
            $items = $this->parser->parse($command->xml);
        } catch (Throwable) {
            return $this->result($node, $parameters, $started, $duration, DiagnosticTestStatus::Failed, 'Die PACS-Antwort konnte nicht verarbeitet werden.', [], ['failureType' => 'parse_error', 'resultCount' => 0], ['Ungültige Antwortstruktur.']);
        }

        $count = count($items);
        $summary = $count === 0 ? 'PACS-Abfrage erfolgreich, keine Treffer.' : "PACS-Abfrage erfolgreich: {$count} Treffer.";

        return $this->result($node, $parameters, $started, $duration, DiagnosticTestStatus::Success, $summary, [
            new DiagnosticTestStep('dicom_association', 'DICOM Association', DiagnosticTestStatus::Success, 0, 'Association akzeptiert.'),
            new DiagnosticTestStep('c_find', 'Study-Root C-FIND', DiagnosticTestStatus::Success, $duration, 'C-FIND erfolgreich ausgeführt.'),
        ], ['exitCode' => $command->exitCode, 'queryModel' => 'study_root', 'resultCount' => $count, 'results' => $items]);
    }

    /**
     * @param  list<DiagnosticTestStep>  $steps
     * @param  array<string, mixed>  $details
     * @param  list<string>  $errors
     */
    private function result(DicomNode $node, PacsQueryParameters $parameters, DateTimeImmutable $started, int $duration, DiagnosticTestStatus $status, string $summary, array $steps, array $details, array $errors = []): DiagnosticTestResult
    {
        return new DiagnosticTestResult(
            (string) Str::uuid7(), 'pacs_query', $status, $started, new DateTimeImmutable, $duration, $summary,
            new DiagnosticTarget($node->host, $node->port, $parameters->calledAeTitle, $parameters->callingAeTitle, $node->public_id, $node->system->public_id),
            $steps, $details, [], $errors,
        );
    }
}
