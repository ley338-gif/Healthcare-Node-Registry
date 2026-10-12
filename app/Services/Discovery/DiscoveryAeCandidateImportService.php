<?php

namespace App\Services\Discovery;

use App\Models\DiscoveryAeCandidate;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use ZipArchive;

final class DiscoveryAeCandidateImportService
{
    private const MAX_ROWS = 30;

    private const MAX_XML_BYTES = 2_097_152;

    /**
     * @return array{
     *   format:string,
     *   rows_processed:int,
     *   candidates:list<array{ae_title:string,role:string,source:string}>,
     *   errors:list<array{line:int,message:string}>,
     *   warnings:list<string>
     * }
     */
    public function preview(UploadedFile $file): array
    {
        $format = strtolower($file->getClientOriginalExtension());
        $table = match ($format) {
            'csv' => $this->readCsv($file),
            'xlsx' => $this->readXlsx($file),
            default => throw new RuntimeException('Nur CSV- oder XLSX-Dateien sind erlaubt.'),
        };

        return ['format' => $format, ...$this->validateTable($table)];
    }

    /** @return list<list<string>> */
    private function readCsv(UploadedFile $file): array
    {
        $path = $file->getRealPath();
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('CSV-Datei konnte nicht geöffnet werden.');
        }

        try {
            $firstLine = fgets($handle);
            if ($firstLine === false) {
                throw new RuntimeException('Die Importdatei ist leer.');
            }
            $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
            rewind($handle);
            $rows = [];
            while (($fields = fgetcsv($handle, separator: $delimiter, escape: '')) !== false) {
                $fields = array_map(static fn (?string $value): string => trim((string) $value), $fields);
                if (! mb_check_encoding(implode('', $fields), 'UTF-8')) {
                    throw new RuntimeException('CSV-Dateien müssen UTF-8-kodiert sein.');
                }
                if (array_filter($fields, static fn (string $value): bool => $value !== '') !== []) {
                    $rows[] = $fields;
                }
                if (count($rows) > self::MAX_ROWS + 1) {
                    throw new RuntimeException('Maximal 30 Datenzeilen pro Import sind erlaubt.');
                }
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    /** @return list<list<string>> */
    private function readXlsx(UploadedFile $file): array
    {
        $archive = new ZipArchive;
        if ($archive->open($file->getRealPath()) !== true) {
            throw new RuntimeException('XLSX-Datei konnte nicht geöffnet werden.');
        }

        try {
            $workbook = $this->xml($this->entry($archive, 'xl/workbook.xml'));
            $workbookXPath = $this->xpath($workbook);
            $sheet = $workbookXPath->query('//x:sheets/x:sheet')?->item(0);
            if (! $sheet instanceof DOMElement) {
                throw new RuntimeException('XLSX-Datei enthält kein Arbeitsblatt.');
            }
            $relationshipId = $sheet->getAttributeNS('http://schemas.openxmlformats.org/officeDocument/2006/relationships', 'id');
            $relationships = $this->xml($this->entry($archive, 'xl/_rels/workbook.xml.rels'));
            $relationshipXPath = new DOMXPath($relationships);
            $relationshipXPath->registerNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');
            $target = '';
            foreach ($relationshipXPath->query('//r:Relationship') ?: [] as $relationship) {
                if ($relationship instanceof DOMElement && $relationship->getAttribute('Id') === $relationshipId) {
                    $target = $relationship->getAttribute('Target');
                    break;
                }
            }
            $sheetPath = $this->worksheetPath($target);
            $sharedStrings = $this->sharedStrings($archive);
            $worksheet = $this->xml($this->entry($archive, $sheetPath));
            $worksheetXPath = $this->xpath($worksheet);
            $rows = [];
            foreach ($worksheetXPath->query('//x:sheetData/x:row') ?: [] as $row) {
                if (! $row instanceof DOMElement) {
                    continue;
                }
                $values = [];
                foreach ($worksheetXPath->query('./x:c', $row) ?: [] as $cell) {
                    if (! $cell instanceof DOMElement) {
                        continue;
                    }
                    $formulas = $worksheetXPath->query('./x:f', $cell);
                    if ($formulas !== false && $formulas->length > 0) {
                        throw new RuntimeException('XLSX-Formeln werden aus Sicherheitsgründen nicht importiert.');
                    }
                    $column = $this->columnIndex($cell->getAttribute('r'));
                    $values[$column] = $this->cellValue($cell, $worksheetXPath, $sharedStrings);
                }
                if ($values !== []) {
                    ksort($values);
                    $lastColumn = (int) array_key_last($values);
                    $rows[] = array_map('trim', array_replace(array_fill(0, $lastColumn + 1, ''), $values));
                }
                if (count($rows) > self::MAX_ROWS + 1) {
                    throw new RuntimeException('Maximal 30 Datenzeilen pro Import sind erlaubt.');
                }
            }

            return $rows;
        } finally {
            $archive->close();
        }
    }

    /**
     * @param  list<list<string>>  $table
     * @return array{rows_processed:int,candidates:list<array{ae_title:string,role:string,source:string}>,errors:list<array{line:int,message:string}>,warnings:list<string>}
     */
    private function validateTable(array $table): array
    {
        if ($table === []) {
            throw new RuntimeException('Die Importdatei ist leer.');
        }

        $headers = array_map(
            static fn (string $header): string => strtolower(trim($header, "\xEF\xBB\xBF \t\n\r\0\x0B")),
            array_shift($table),
        );
        foreach (['schema_version', 'ae_title'] as $required) {
            if (! in_array($required, $headers, true)) {
                throw new RuntimeException("Fehlende Importspalte: {$required}.");
            }
        }

        $candidates = [];
        $errors = [];
        $warnings = [];
        $seen = [];
        foreach ($table as $index => $fields) {
            $fields = array_pad($fields, count($headers), '');
            /** @var array<string, string> $record */
            $record = array_combine($headers, array_slice($fields, 0, count($headers)));
            $line = $index + 2;
            $version = trim($record['schema_version']);
            $title = strtoupper(trim($record['ae_title']));
            $role = strtolower(trim($record['role'] ?? 'called')) ?: DiscoveryAeCandidate::ROLE_CALLED;

            if ($version !== '1') {
                $errors[] = ['line' => $line, 'message' => 'schema_version muss 1 sein.'];
            }
            if ($title === '' || mb_strlen($title) > 16 || preg_match('/^[A-Z0-9 _-]+$/', $title) !== 1) {
                $errors[] = ['line' => $line, 'message' => 'AE-Titel muss 1–16 zulässige Zeichen enthalten.'];
            }
            if (! in_array($role, DiscoveryAeCandidate::ROLES, true)) {
                $errors[] = ['line' => $line, 'message' => 'Rolle muss calling oder called sein.'];
            }
            if (collect($errors)->contains(fn (array $error): bool => $error['line'] === $line)) {
                continue;
            }

            $key = $role.'|'.$title;
            if (isset($seen[$key])) {
                $warnings[] = "Zeile {$line}: Doppelter Kandidat {$title} ({$role}) wurde übersprungen.";

                continue;
            }
            $seen[$key] = true;
            $candidates[] = ['ae_title' => $title, 'role' => $role, 'source' => DiscoveryAeCandidate::SOURCE_IMPORTED];
        }

        return [
            'rows_processed' => count($table),
            'candidates' => $candidates,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    private function entry(ZipArchive $archive, string $name): string
    {
        $stat = $archive->statName($name);
        if (! is_array($stat) || (int) ($stat['size'] ?? 0) > self::MAX_XML_BYTES) {
            throw new RuntimeException('XLSX-Struktur fehlt oder überschreitet das Sicherheitslimit.');
        }
        $contents = $archive->getFromName($name);
        if (! is_string($contents)) {
            throw new RuntimeException('XLSX-Struktur konnte nicht gelesen werden.');
        }

        return $contents;
    }

    private function xml(string $contents): DOMDocument
    {
        $this->assertUtf8Xml($contents);
        if (stripos($contents, '<!DOCTYPE') !== false) {
            throw new RuntimeException('XLSX-Datei enthält eine unzulässige XML-Deklaration.');
        }
        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($contents, LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if (! $loaded) {
            throw new RuntimeException('XLSX-Datei enthält ungültiges XML.');
        }

        return $document;
    }

    /**
     * The byte-level DOCTYPE check only holds if libxml2 decodes exactly these
     * bytes, so UTF-16/32, EBCDIC and declared non-UTF-8 encodings such as
     * UTF-7 are rejected before parsing.
     */
    private function assertUtf8Xml(string $contents): void
    {
        if (str_contains($contents, "\0") || ! mb_check_encoding($contents, 'UTF-8')) {
            throw new RuntimeException('XLSX-Datei muss UTF-8-kodiertes XML enthalten.');
        }

        if (preg_match('/\A(?:\xEF\xBB\xBF)?<\?xml\b(.*?)\?>/s', $contents, $declaration) !== 1) {
            return;
        }

        $encodings = preg_match_all('/encoding/i', $declaration[1]);
        $utf8Encodings = preg_match_all('/\bencoding\s*=\s*(["\'])utf-8\1/i', $declaration[1]);
        if ($encodings !== $utf8Encodings) {
            throw new RuntimeException('XLSX-Datei muss UTF-8-kodiertes XML enthalten.');
        }
    }

    private function xpath(DOMDocument $document): DOMXPath
    {
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('x', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        return $xpath;
    }

    private function worksheetPath(string $target): string
    {
        $target = str_replace('\\', '/', trim($target));
        $path = str_starts_with($target, '/') ? ltrim($target, '/') : 'xl/'.ltrim($target, '/');
        if ($target === '' || str_contains($path, '..') || ! str_starts_with($path, 'xl/worksheets/')) {
            throw new RuntimeException('XLSX-Arbeitsblatt verweist auf einen unzulässigen Pfad.');
        }

        return $path;
    }

    /** @return list<string> */
    private function sharedStrings(ZipArchive $archive): array
    {
        if ($archive->locateName('xl/sharedStrings.xml') === false) {
            return [];
        }
        $document = $this->xml($this->entry($archive, 'xl/sharedStrings.xml'));
        $xpath = $this->xpath($document);
        $strings = [];
        foreach ($xpath->query('//x:si') ?: [] as $item) {
            $value = '';
            foreach ($xpath->query('.//x:t', $item) ?: [] as $text) {
                $value .= $text->textContent;
            }
            $strings[] = $value;
        }

        return $strings;
    }

    /** @param list<string> $sharedStrings */
    private function cellValue(DOMElement $cell, DOMXPath $xpath, array $sharedStrings): string
    {
        if ($cell->getAttribute('t') === 'inlineStr') {
            $value = '';
            foreach ($xpath->query('.//x:is/x:t', $cell) ?: [] as $text) {
                $value .= $text->textContent;
            }

            return $value;
        }
        $valueNodes = $xpath->query('./x:v', $cell);
        $raw = $valueNodes === false || $valueNodes->length === 0 ? '' : $valueNodes->item(0)->textContent;
        if ($cell->getAttribute('t') === 's') {
            return $sharedStrings[(int) $raw] ?? '';
        }

        return $raw;
    }

    private function columnIndex(string $reference): int
    {
        preg_match('/^[A-Z]+/i', $reference, $matches);
        $letters = strtoupper($matches[0] ?? 'A');
        $index = 0;
        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + ord($letter) - 64;
        }

        return max(0, $index - 1);
    }
}
