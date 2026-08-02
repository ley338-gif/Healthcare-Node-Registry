<?php

namespace App\Services\Imports;

use App\Http\Requests\StoreDicomNodeRequest;
use App\Http\Requests\StoreSystemRequest;
use App\Models\Department;
use App\Models\DicomNode;
use App\Models\Organization;
use App\Models\Site;
use App\Models\System;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

final class RegistryCsvImportService
{
    private const SYSTEM_HEADERS = ['organization_name', 'site_name', 'department_name', 'name', 'system_type', 'status', 'hostname', 'fqdn', 'ip_address', 'vendor', 'product', 'model', 'version', 'operating_system', 'operating_system_version', 'serial_number', 'inventory_number', 'description', 'notes'];

    private const NODE_HEADERS = ['organization_name', 'system_name', 'name', 'ae_title', 'host', 'port', 'role', 'status', 'tls_enabled', 'supports_echo', 'supports_store', 'supports_query', 'supports_retrieve', 'supports_storage_commitment', 'supports_mpps', 'supports_worklist', 'description', 'notes'];

    /** @return array{kind:string,headers:list<string>,rows:list<array<string,mixed>>,valid:int,invalid:int} */
    public function preview(string $kind, UploadedFile $file): array
    {
        $records = $this->read($file, $kind);
        $seen = [];
        $rows = [];
        foreach ($records as $index => $record) {
            [$values, $errors, $key] = $kind === 'systems' ? $this->systemRow($record) : $this->nodeRow($record);
            if ($key !== null && isset($seen[$key])) {
                $errors[] = 'Duplikat innerhalb der CSV-Datei.';
            }
            if ($key !== null) {
                $seen[$key] = true;
            }
            $rows[] = ['line' => $index + 2, 'source' => $record, 'values' => $values, 'errors' => array_values(array_unique($errors)), 'valid' => $errors === []];
        }

        $valid = count(array_filter($rows, static fn (array $row): bool => $row['valid']));

        return ['kind' => $kind, 'headers' => $kind === 'systems' ? self::SYSTEM_HEADERS : self::NODE_HEADERS, 'rows' => $rows, 'valid' => $valid, 'invalid' => count($rows) - $valid];
    }

    /** @param array{kind:string,rows:list<array<string,mixed>>} $preview
     * @return array{imported:int,skipped:int,failed:int}
     */
    public function import(array $preview): array
    {
        $imported = 0;
        $skipped = 0;
        $failed = 0;
        DB::transaction(function () use ($preview, &$imported, &$skipped, &$failed): void {
            foreach ($preview['rows'] as $row) {
                if (! $row['valid']) {
                    $failed++;

                    continue;
                }
                /** @var array<string,mixed> $values */
                $values = $row['values'];
                if ($preview['kind'] === 'systems') {
                    $duplicate = System::query()->where('organization_id', $values['organization_id'])->whereRaw('lower(name) = lower(?)', [$values['name']])->exists();
                    if ($duplicate) {
                        $skipped++;

                        continue;
                    }
                    System::query()->create($values);
                } else {
                    $duplicate = DicomNode::query()->where('system_id', $values['system_id'])->where('ae_title', strtoupper((string) $values['ae_title']))->where('host', $values['host'])->where('port', $values['port'])->exists();
                    if ($duplicate) {
                        $skipped++;

                        continue;
                    }
                    DicomNode::query()->create($values);
                }
                $imported++;
            }
        });

        return compact('imported', 'skipped', 'failed');
    }

    /** @return list<array<string,string>> */
    private function read(UploadedFile $file, string $kind): array
    {
        $handle = fopen($file->getRealPath(), 'rb');
        if ($handle === false) {
            throw new RuntimeException('CSV-Datei konnte nicht geöffnet werden.');
        }
        try {
            $header = fgetcsv($handle, escape: '');
            if ($header === false) {
                throw new RuntimeException('CSV-Datei enthält keine Kopfzeile.');
            }
            $header = array_map(static fn (string $value): string => strtolower(trim($value, "\xEF\xBB\xBF \t\n\r\0\x0B")), $header);
            $expected = $kind === 'systems' ? self::SYSTEM_HEADERS : self::NODE_HEADERS;
            $missing = array_diff($expected, $header);
            if ($missing !== []) {
                throw new RuntimeException('Fehlende CSV-Spalten: '.implode(', ', $missing));
            }
            $records = [];
            while (($fields = fgetcsv($handle, escape: '')) !== false) {
                if (count($records) >= 1000) {
                    throw new RuntimeException('Maximal 1000 Datenzeilen pro Import sind erlaubt.');
                }
                $fields = array_pad($fields, count($header), '');
                /** @var array<string,string> $record */
                $record = array_combine($header, array_slice($fields, 0, count($header)));
                if (count(array_filter($record, static fn (string $value): bool => trim($value) !== '')) > 0) {
                    $records[] = array_map('trim', $record);
                }
            }

            return $records;
        } finally {
            fclose($handle);
        }
    }

    /** @param array<string,string> $record
     * @return array{array<string,mixed>,list<string>,string|null}
     */
    private function systemRow(array $record): array
    {
        $organization = Organization::query()->whereRaw('lower(name) = lower(?)', [$record['organization_name']])->whereNull('archived_at')->first();
        $site = $record['site_name'] === '' || $organization === null ? null : Site::query()->where('organization_id', $organization->id)->whereRaw('lower(name) = lower(?)', [$record['site_name']])->whereNull('archived_at')->first();
        $department = $record['department_name'] === '' || $organization === null ? null : Department::query()->whereHas('site', fn ($query) => $query->where('organization_id', $organization->id))->when($site !== null, fn ($query) => $query->where('site_id', $site->id))->whereRaw('lower(name) = lower(?)', [$record['department_name']])->whereNull('archived_at')->first();
        $values = ['organization_id' => $organization?->id, 'site_id' => $site?->id, 'department_id' => $department?->id];
        foreach (array_slice(self::SYSTEM_HEADERS, 3) as $field) {
            $values[$field] = $record[$field] === '' ? null : $record[$field];
        }
        $validator = Validator::make($values, (new StoreSystemRequest)->rules());
        $errors = $validator->errors()->all();
        if ($organization === null) {
            $errors[] = 'Organisation wurde nicht gefunden.';
        }
        if ($record['site_name'] !== '' && $site === null) {
            $errors[] = 'Standort wurde in der Organisation nicht gefunden.';
        }
        if ($record['department_name'] !== '' && $department === null) {
            $errors[] = 'Abteilung wurde in der Organisation nicht gefunden.';
        }
        if ($organization !== null && System::query()->where('organization_id', $organization->id)->whereRaw('lower(name) = lower(?)', [$record['name']])->exists()) {
            $errors[] = 'Systemname ist in dieser Organisation bereits vorhanden.';
        }

        return [$values, $errors, $organization === null ? null : $organization->id.'|'.mb_strtolower($record['name'])];
    }

    /** @param array<string,string> $record
     * @return array{array<string,mixed>,list<string>,string|null}
     */
    private function nodeRow(array $record): array
    {
        $systems = System::query()->whereHas('organization', fn ($query) => $query->whereRaw('lower(name) = lower(?)', [$record['organization_name']]))->whereRaw('lower(name) = lower(?)', [$record['system_name']])->whereNull('archived_at')->limit(2)->get();
        $system = $systems->count() === 1 ? $systems->first() : null;
        $values = ['system_id' => $system?->id];
        foreach (array_slice(self::NODE_HEADERS, 2) as $field) {
            $values[$field] = str_starts_with($field, 'supports_') || $field === 'tls_enabled' ? $this->boolean($record[$field]) : ($record[$field] === '' ? null : $record[$field]);
        }
        $form = new StoreDicomNodeRequest;
        $validator = Validator::make($values, $form->rulesForSystem($system?->id, $values['host'], $values['port']), $form->messages());
        $errors = $validator->errors()->all();
        if ($system === null) {
            $errors[] = 'System wurde in der angegebenen Organisation nicht eindeutig gefunden.';
        }

        return [$values, $errors, $system === null ? null : $system->id.'|'.strtoupper((string) $values['ae_title']).'|'.mb_strtolower((string) $values['host']).'|'.$values['port']];
    }

    private function boolean(string $value): mixed
    {
        return match (strtolower(trim($value))) {
            '1', 'true', 'yes', 'ja' => true,
            '0', 'false', 'no', 'nein' => false,
            default => $value,
        };
    }
}
