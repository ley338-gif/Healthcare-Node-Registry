<?php

namespace App\Services\Diagnostics;

use App\Models\DiagnosticTestRun;

final class DiagnosticResultExporter
{
    /** @return array<string, mixed> */
    public function payload(DiagnosticTestRun $run): array
    {
        $run->loadMissing(['user:id,public_id,name', 'dicomNode:id,public_id,name', 'system:id,public_id,name']);

        return $this->sanitize([
            'testId' => $run->public_id,
            'testType' => $run->test_type,
            'status' => $run->status,
            'startedAt' => $run->started_at->toIso8601String(),
            'finishedAt' => $run->finished_at->toIso8601String(),
            'durationMilliseconds' => $run->duration_ms,
            'resultCount' => $run->result_count,
            'user' => $run->user ? ['publicId' => $run->user->public_id, 'name' => $run->user->name] : null,
            'dicomNode' => ['publicId' => $run->dicomNode->public_id, 'name' => $run->dicomNode->name],
            'system' => ['publicId' => $run->system->public_id, 'name' => $run->system->name],
            'target' => ['host' => $run->target_host, 'port' => $run->target_port, 'callingAeTitle' => $run->calling_ae_title, 'calledAeTitle' => $run->called_ae_title],
            'summary' => $run->summary,
            'steps' => $run->steps,
            'details' => $run->details,
            'warnings' => $run->warnings,
            'errors' => $run->errors,
        ]);
    }

    public function json(DiagnosticTestRun $run): string
    {
        return (string) json_encode($this->payload($run), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public function csv(DiagnosticTestRun $run): string
    {
        $payload = $this->payload($run);
        $details = $payload['details'];
        $rows = is_array($details) && isset($details['results']) && is_array($details['results'])
            ? $details['results']
            : (is_array($details) && isset($details['matrix']) && is_array($details['matrix']) ? $details['matrix'] : [[
                'testId' => $payload['testId'], 'testType' => $payload['testType'], 'status' => $payload['status'],
                'startedAt' => $payload['startedAt'], 'durationMilliseconds' => $payload['durationMilliseconds'], 'summary' => $payload['summary'],
            ]]);
        /** @var list<array<string, mixed>> $rows */
        $normalized = array_values(array_filter($rows, 'is_array'));
        if ($normalized === []) {
            return '';
        }
        $headers = array_values(array_unique(array_merge(...array_map('array_keys', $normalized))));
        $stream = fopen('php://temp', 'w+');
        if ($stream === false) {
            return '';
        }
        fputcsv($stream, $headers, ',', '"', '');
        foreach ($normalized as $row) {
            fputcsv($stream, array_map(static function (string $header) use ($row): string {
                $value = $row[$header] ?? null;

                return is_scalar($value) || $value === null ? (string) $value : (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }, $headers), ',', '"', '');
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return $csv === false ? '' : $csv;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function sanitize(array $values): array
    {
        $safe = [];
        foreach ($values as $key => $value) {
            if (preg_match('/password|passwd|secret|token|authorization|patientname|patientid|patientbirthdate|accessionnumber|stacktrace|stack_trace/i', $key) === 1) {
                $safe[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                /** @var array<string, mixed> $value */
                $safe[$key] = $this->sanitize($value);
            } elseif (is_string($value)) {
                $safe[$key] = preg_replace('~(?:[A-Z]:\\\\(?:[^\\\\\s]+\\\\)*[^\\\\\s]+)|(?:/(?:[^/\s]+/)+[^/\s]+)~i', '[INTERNAL_PATH]', $value) ?? $value;
            } else {
                $safe[$key] = $value;
            }
        }

        return $safe;
    }
}
