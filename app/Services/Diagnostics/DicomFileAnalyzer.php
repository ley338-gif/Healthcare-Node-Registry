<?php

namespace App\Services\Diagnostics;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

final class DicomFileAnalyzer
{
    public function __construct(private readonly ?DicomDumpRunner $runner = null) {}

    public function analyze(UploadedFile $upload): DicomFileAnalysisResult
    {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'hnr-analyze-'.Str::random(20);
        $path = $directory.DIRECTORY_SEPARATOR.'upload.dcm';
        try {
            if (! mkdir($directory, 0700) && ! is_dir($directory)) {
                throw new RuntimeException('Temporäres Analyseverzeichnis konnte nicht erstellt werden.');
            }
            if (! copy($upload->getRealPath(), $path)) {
                throw new RuntimeException('Upload konnte nicht sicher kopiert werden.');
            }
            $preamble = $this->hasPreamble($path);
            $result = ($this->runner ?? new NativeDicomDumpRunner)->run($path);
            if (! $result->successful) {
                $message = $result->timedOut ? 'Zeitüberschreitung bei der DICOM-Analyse.' : 'Die Datei konnte nicht als gültiges DICOM-Objekt gelesen werden.';

                return new DicomFileAnalysisResult(false, ['fileSize' => filesize($path), 'hasPreamble' => $preamble], [], [$message], '');
            }

            $tags = $this->tags($result->output);
            $warnings = [];
            foreach (['sopClassUid', 'sopInstanceUid', 'studyInstanceUid', 'seriesInstanceUid'] as $required) {
                if (($tags[$required] ?? null) === null) {
                    $warnings[] = "Pflichtwert {$required} fehlt.";
                }
            }
            foreach (['sopClassUid', 'sopInstanceUid', 'studyInstanceUid', 'seriesInstanceUid'] as $uidKey) {
                $uid = $tags[$uidKey] ?? null;
                if (is_string($uid) && preg_match('/^[0-9]+(?:\.[0-9]+)+$/', $uid) !== 1) {
                    $warnings[] = "{$uidKey} ist keine gültige UID.";
                }
            }
            $privateTags = preg_match_all('/\([0-9a-f]{4},[0-9a-f]{4}\).*Private/i', $result->output);
            $summary = [
                'fileSize' => filesize($path), 'hasPreamble' => $preamble,
                'transferSyntax' => $tags['transferSyntax'] ?? null, 'mediaStorageSopClassUid' => $tags['mediaStorageSopClassUid'] ?? null,
                'sopClassUid' => $tags['sopClassUid'] ?? null, 'sopInstanceUid' => $tags['sopInstanceUid'] ?? null,
                'studyInstanceUid' => $tags['studyInstanceUid'] ?? null, 'seriesInstanceUid' => $tags['seriesInstanceUid'] ?? null,
                'patientId' => $this->mask($tags['patientId'] ?? null), 'accessionNumber' => $this->mask($tags['accessionNumber'] ?? null),
                'specificCharacterSet' => $tags['specificCharacterSet'] ?? null, 'privateTagCount' => $privateTags === false ? 0 : $privateTags,
                'hasPixelData' => str_contains($result->output, '(7fe0,0010)'),
                'compressed' => $this->compressed($tags['transferSyntax'] ?? null),
            ];

            return new DicomFileAnalysisResult(true, $summary, $warnings, [], $this->sanitizeDump($result->output));
        } catch (RuntimeException $exception) {
            return new DicomFileAnalysisResult(false, [], [], [$exception->getMessage()], '');
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }

    private function hasPreamble(string $path): bool
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return false;
        }
        fseek($handle, 128);
        $marker = fread($handle, 4);
        fclose($handle);

        return $marker === 'DICM';
    }

    /** @return array<string, string> */
    private function tags(string $dump): array
    {
        $map = ['0002,0002' => 'mediaStorageSopClassUid', '0002,0010' => 'transferSyntax', '0008,0005' => 'specificCharacterSet', '0008,0016' => 'sopClassUid', '0008,0018' => 'sopInstanceUid', '0008,0050' => 'accessionNumber', '0010,0020' => 'patientId', '0020,000d' => 'studyInstanceUid', '0020,000e' => 'seriesInstanceUid'];
        $tags = [];
        foreach ($map as $number => $key) {
            if (preg_match('/^\('.preg_quote($number, '/').'\)[^\r\n\[]*\[([^\]\r\n]*)\]/mi', $dump, $matches) === 1) {
                $tags[$key] = trim($matches[1]);
            }
        }

        return $tags;
    }

    private function mask(?string $value): ?string
    {
        return $value === null || $value === '' ? null : '[REDACTED]';
    }

    private function compressed(?string $syntax): ?bool
    {
        if ($syntax === null) {
            return null;
        }

        return ! in_array($syntax, ['1.2.840.10008.1.2', '1.2.840.10008.1.2.1', '1.2.840.10008.1.2.2'], true);
    }

    private function sanitizeDump(string $dump): string
    {
        $dump = preg_replace('/^(.*\((?:0010,0010|0010,0020|0010,0030|0008,0050)\).*\[)[^\]]*(\].*)$/mi', '$1[REDACTED]$2', $dump) ?? $dump;
        $dump = preg_replace('~(?:[A-Z]:\\\\(?:[^\\\\\s]+\\\\)*[^\\\\\s]+)|(?:/(?:[^/\s]+/)+[^/\s]+)~i', '[INTERNAL_PATH]', $dump) ?? $dump;

        return mb_substr(trim($dump), 0, 50000);
    }
}
