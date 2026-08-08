<?php

namespace App\Services\Discovery;

use App\Models\DicomNode;
use App\Models\DiscoveredHost;
use App\Models\System;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Übernahme eines Discovery-Fundes in die System-Registry (Abschnitt 13)
 * inklusive Duplikaterkennung (Abschnitt 14). Modelliert nach dem
 * preview()/promote()-Muster von App\Services\Imports\RegistryCsvImportService:
 * Duplikate werden angezeigt, nie automatisch überschrieben.
 */
final class RegistryPromotionService
{
    /**
     * @return list<array{type: string, system: System, dicom_node: DicomNode|null}>
     */
    public function findDuplicates(DiscoveredHost $host, string $proposedName, string $proposedAeTitle, int $proposedPort): array
    {
        $matches = [];
        $seenSystemIds = [];

        $addMatch = function (string $type, System $system, ?DicomNode $node = null) use (&$matches, &$seenSystemIds): void {
            $nodeKey = $node !== null ? $node->id : '0';
            $key = $type.':'.$system->id.':'.$nodeKey;
            if (isset($seenSystemIds[$key])) {
                return;
            }
            $seenSystemIds[$key] = true;
            $matches[] = ['type' => $type, 'system' => $system, 'dicom_node' => $node];
        };

        System::query()->active()->where(fn ($query) => $query->where('ip_address', $host->ip_address)->orWhereHas('networkInterfaces', fn ($interfaces) => $interfaces->where('ip_address', $host->ip_address)))->get()
            ->each(fn (System $system) => $addMatch('ip_address', $system));

        if (filled($host->hostname)) {
            System::query()->active()->where(fn ($query) => $query->whereRaw('lower(hostname) = lower(?)', [$host->hostname])->orWhereHas('networkInterfaces', fn ($interfaces) => $interfaces->whereRaw('lower(hostname) = lower(?)', [$host->hostname])))->get()
                ->each(fn (System $system) => $addMatch('hostname', $system));
        }

        DicomNode::query()->active()->where('ae_title', mb_strtoupper($proposedAeTitle))
            ->with('system')->get()
            ->each(function (DicomNode $node) use ($addMatch): void {
                if ($node->system !== null) {
                    $addMatch('ae_title', $node->system, $node);
                }
            });

        $hostIdentifier = $host->hostname ?: $host->ip_address;
        DicomNode::query()->active()
            ->where('port', $proposedPort)
            ->where(fn ($query) => $query->where('host', $host->ip_address)->orWhere('host', $hostIdentifier))
            ->with('system')->get()
            ->each(function (DicomNode $node) use ($addMatch): void {
                if ($node->system !== null) {
                    $addMatch('ip_and_port', $node->system, $node);
                }
            });

        /** @var Collection<int, System> $candidates */
        $candidates = System::query()->active()->get(['id', 'name']);
        $normalizedProposed = mb_strtolower(trim($proposedName));
        foreach ($candidates as $system) {
            $normalizedExisting = mb_strtolower(trim($system->name));
            if ($normalizedExisting === $normalizedProposed) {
                continue; // bereits über andere Kriterien oder exakte Übereinstimmung, kein zusätzlicher "ähnlicher Name"-Hinweis nötig
            }
            if (levenshtein($normalizedExisting, $normalizedProposed) <= 2) {
                $addMatch('similar_name', $system);
            }
        }

        return $matches;
    }

    /**
     * @param  array<string, mixed>  $systemData
     * @param  array<string, mixed>  $dicomNodeData
     */
    public function promote(
        DiscoveredHost $host,
        array $systemData,
        array $dicomNodeData,
        ?System $existingSystem,
        ?int $discoveryRunId,
        ?int $originalConfidencePercentage,
    ): System {
        return DB::transaction(function () use ($host, $systemData, $dicomNodeData, $existingSystem, $discoveryRunId, $originalConfidencePercentage): System {
            $provenanceNote = sprintf(
                '[Discovery] Quelle: Discovery-Lauf #%s, erkannt am %s, ursprünglicher Confidence-Score: %s%%.',
                $discoveryRunId ?? 'unbekannt',
                $host->last_seen_at?->toDateTimeString() ?? now()->toDateTimeString(),
                $originalConfidencePercentage ?? 0,
            );

            if ($existingSystem !== null) {
                $existingSystem->update($systemData);
                $system = $existingSystem;
            } else {
                $system = System::query()->create([
                    ...$systemData,
                    'notes' => trim(($systemData['notes'] ?? '')."\n".$provenanceNote),
                ]);
            }

            DicomNode::query()->firstOrCreate(
                [
                    'system_id' => $system->id,
                    'ae_title' => mb_strtoupper((string) $dicomNodeData['ae_title']),
                    'host' => $dicomNodeData['host'],
                    'port' => $dicomNodeData['port'],
                ],
                [
                    ...$dicomNodeData,
                    'name' => $dicomNodeData['name'] ?? ($system->name.' Discovery-Endpunkt'),
                    'role' => $dicomNodeData['role'] ?? 'scp',
                    'status' => 'active',
                    'supports_echo' => true,
                    'last_verified_at' => now(),
                    'last_verification_status' => 'success',
                    'description' => $provenanceNote,
                ],
            );

            $host->update([
                'system_id' => $system->id,
                'status' => DiscoveredHost::STATUS_CONFIRMED,
            ]);

            return $system;
        });
    }
}
