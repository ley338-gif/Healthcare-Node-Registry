<?php

namespace App\Services\Diagnostics;

use App\Models\DiagnosticTestRun;
use App\Models\DicomNode;
use App\Models\User;

final class DiagnosticTestRecorder
{
    public function record(
        DiagnosticTestResult $result,
        DicomNode $dicomNode,
        ?User $user,
    ): DiagnosticTestRun {
        $details = $this->sanitize($result->details);
        $steps = array_map(
            fn (DiagnosticTestStep $step): array => $this->sanitize($step->toArray()),
            $result->steps,
        );

        return DiagnosticTestRun::query()->create([
            'public_id' => $result->testId,
            'user_id' => $user?->id,
            'dicom_node_id' => $dicomNode->id,
            'system_id' => $dicomNode->system_id,
            'test_type' => $result->testType,
            'status' => $result->status->value,
            'started_at' => $result->startedAt,
            'finished_at' => $result->finishedAt,
            'duration_ms' => $result->durationMilliseconds,
            'result_count' => $this->resultCount($details),
            'target_host' => $dicomNode->host,
            'target_port' => $dicomNode->port,
            'calling_ae_title' => $result->target->callingAeTitle,
            'called_ae_title' => $result->target->calledAeTitle,
            'summary' => $result->summary,
            'steps' => $steps,
            'details' => $details,
            'warnings' => $result->warnings,
            'errors' => $result->errors,
            'sanitized_log_excerpt' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function sanitize(array $values): array
    {
        $sanitized = [];

        foreach ($values as $key => $value) {
            if (preg_match('/password|passwd|secret|token|authorization/i', $key) === 1) {
                $sanitized[$key] = '[REDACTED]';

                continue;
            }

            if (is_array($value)) {
                /** @var array<string, mixed> $value */
                $sanitized[$key] = $this->sanitize($value);

                continue;
            }

            if (is_string($value)) {
                $value = preg_replace(
                    '~(?:[A-Z]:\\\\(?:[^\\\\\s]+\\\\)*[^\\\\\s]+)|(?:/(?:[^/\s]+/)+[^/\s]+)~i',
                    '[INTERNAL_PATH]',
                    $value,
                ) ?? $value;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    /** @param array<string, mixed> $details */
    private function resultCount(array $details): ?int
    {
        $count = $details['resultCount'] ?? null;

        return is_int($count) && $count >= 0 ? $count : null;
    }
}
