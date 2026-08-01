<?php

namespace App\Services\Documents;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use ZipArchive;

final class RegistryDocumentFileInspector
{
    /** @return array{extension: string, mime_type: string, sha256: string, size_bytes: int} */
    public function inspect(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = (string) $file->getMimeType();
        $path = $file->getRealPath();
        $allowedMimes = [
            'pdf' => ['application/pdf'], 'png' => ['image/png'],
            'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
            'txt' => ['text/plain'], 'zip' => ['application/zip', 'application/x-zip-compressed'],
        ];
        if (! isset($allowedMimes[$extension]) || ! in_array($mime, $allowedMimes[$extension], true)) {
            throw ValidationException::withMessages(['file' => 'Dateityp oder MIME-Typ ist nicht erlaubt.']);
        }
        $header = file_get_contents($path, false, null, 0, 16);
        if (! is_string($header) || ! $this->signatureMatches($extension, $header, $path)) {
            throw ValidationException::withMessages(['file' => 'Die Dateisignatur passt nicht zum angegebenen Dateityp.']);
        }
        $hash = hash_file('sha256', $path);
        $size = filesize($path);
        if (! is_string($hash) || ! is_int($size)) {
            throw ValidationException::withMessages(['file' => 'Die Datei konnte nicht sicher geprüft werden.']);
        }

        return ['extension' => $extension, 'mime_type' => $mime, 'sha256' => $hash, 'size_bytes' => $size];
    }

    private function signatureMatches(string $extension, string $header, string $path): bool
    {
        return match ($extension) {
            'pdf' => str_starts_with($header, '%PDF-'),
            'png' => str_starts_with($header, "\x89PNG\r\n\x1a\n"),
            'jpg', 'jpeg' => str_starts_with($header, "\xff\xd8\xff"),
            'txt' => $this->isPlainText($header, $path),
            'zip' => str_starts_with($header, 'PK'),
            'docx' => $this->officeArchiveMatches($path, 'word/'),
            'xlsx' => $this->officeArchiveMatches($path, 'xl/'),
            default => false,
        };
    }

    private function officeArchiveMatches(string $path, string $directory): bool
    {
        $archive = new ZipArchive;
        if ($archive->open($path) !== true) {
            return false;
        }
        $hasDirectory = false;
        for ($index = 0; $index < $archive->numFiles; $index++) {
            $name = $archive->getNameIndex($index);
            if (is_string($name) && str_starts_with($name, $directory)) {
                $hasDirectory = true;
                break;
            }
        }
        $matches = $archive->locateName('[Content_Types].xml') !== false && $hasDirectory;
        $archive->close();

        return $matches;
    }

    private function isPlainText(string $header, string $path): bool
    {
        $contents = file_get_contents($path);

        return ! str_contains($header, "\0") && is_string($contents) && mb_check_encoding($contents, 'UTF-8');
    }
}
