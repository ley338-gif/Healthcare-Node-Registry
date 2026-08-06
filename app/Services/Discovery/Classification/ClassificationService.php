<?php

namespace App\Services\Discovery\Classification;

use App\Models\DiscoveredHost;

/**
 * Einfache, vollständig regelbasierte Klassifizierung (Abschnitt 12) -
 * keine externe KI, keine Modellaufrufe. Jede Regel ist ein benannter,
 * gewichteter, für sich lesbarer Hinweis. Das Ergebnis ist ausdrücklich
 * eine Heuristik ohne Anspruch auf wissenschaftliche Genauigkeit und muss
 * in der Oberfläche entsprechend gekennzeichnet werden.
 */
final class ClassificationService
{
    /**
     * Suchbegriff => vorgeschlagener Systemtyp. Reihenfolge = Priorität
     * bei mehreren Treffern im selben Text.
     *
     * @var array<string,string>
     */
    private const TYPE_KEYWORDS = [
        'PACS' => 'pacs',
        'ARCHIVE' => 'archiv',
        'MWL' => 'worklist_server',
        'WORKLIST' => 'worklist_server',
        'RIS' => 'ris',
        'VIEWPOINT' => 'viewer',
        'VIEWER' => 'viewer',
        'ROUTER' => 'dicom_router',
        'PRINT' => 'dicom_drucker',
        'MAMMO' => 'mammographie',
        'CT' => 'ct',
        'MR' => 'mrt',
        'MRI' => 'mrt',
        'US' => 'ultraschall',
        'SONO' => 'ultraschall',
        'XA' => 'roentgen',
        'CR' => 'roentgen',
        'DX' => 'roentgen',
    ];

    public function classify(DiscoveredHost $host, ClassificationContext $context): ClassificationResult
    {
        $evidence = [];

        if ($context->successfulEchoes !== []) {
            foreach ($context->successfulEchoes as $echo) {
                $evidence[] = new ClassificationEvidence(
                    'dicom_echo_successful',
                    "Erfolgreicher C-ECHO auf Port {$echo['port']} mit Called AE Title {$echo['called_ae']}.",
                    40,
                );
            }
        }

        foreach ($context->openDicomCandidatePorts as $port) {
            $evidence[] = new ClassificationEvidence(
                'dicom_candidate_port_open',
                "Port {$port} ist offen und wurde als DICOM-Kandidat markiert (beweist allein noch keinen DICOM-Dienst).",
                15,
            );
        }

        $hostname = mb_strtoupper((string) $host->hostname);
        foreach (self::TYPE_KEYWORDS as $keyword => $type) {
            if ($hostname !== '' && str_contains($hostname, $keyword)) {
                $evidence[] = new ClassificationEvidence(
                    'hostname_keyword_match',
                    "Hostname \"{$host->hostname}\" enthält den Begriff \"{$keyword}\".",
                    10,
                );
            }
        }

        foreach ($context->successfulEchoes as $echo) {
            $calledAe = mb_strtoupper($echo['called_ae']);
            foreach (self::TYPE_KEYWORDS as $keyword => $type) {
                if (str_contains($calledAe, $keyword)) {
                    $evidence[] = new ClassificationEvidence(
                        'ae_title_keyword_match',
                        "AE-Titel \"{$echo['called_ae']}\" enthält den Begriff \"{$keyword}\".",
                        10,
                    );
                }
            }
        }

        if ($context->existingRegistryMatch) {
            $evidence[] = new ClassificationEvidence(
                'existing_registry_match',
                'IP-Adresse, Hostname oder AE-Titel stimmt mit einem bereits vorhandenen Registry-Eintrag überein.',
                20,
            );
        }

        if ($context->webPortOpen) {
            $evidence[] = new ClassificationEvidence(
                'web_port_open',
                'Web-Port (80/443) ist offen - möglicher Verwaltungszugang eines Servers.',
                5,
            );
        }

        if ($context->databasePortOpen) {
            $evidence[] = new ClassificationEvidence(
                'database_port_open',
                'Ein bekannter Datenbank-Port ist offen - möglicher Datenbankserver im Hintergrund.',
                5,
            );
        }

        $percentage = min(100, array_sum(array_map(static fn (ClassificationEvidence $item): int => $item->weight, $evidence)));

        return new ClassificationResult(
            evidence: $evidence,
            percentage: $percentage,
            confidenceLevel: $this->bucket($percentage),
            suggestedSystemType: $this->suggestType($hostname, $context),
        );
    }

    private function bucket(int $percentage): string
    {
        return match (true) {
            $percentage >= 80 => DiscoveredHost::CONFIDENCE_VERY_HIGH,
            $percentage >= 55 => DiscoveredHost::CONFIDENCE_HIGH,
            $percentage >= 30 => DiscoveredHost::CONFIDENCE_MEDIUM,
            $percentage >= 10 => DiscoveredHost::CONFIDENCE_LOW,
            $percentage > 0 => DiscoveredHost::CONFIDENCE_VERY_LOW,
            default => DiscoveredHost::CONFIDENCE_UNKNOWN,
        };
    }

    private function suggestType(string $hostname, ClassificationContext $context): string
    {
        foreach (self::TYPE_KEYWORDS as $keyword => $type) {
            if ($hostname !== '' && str_contains($hostname, $keyword)) {
                return $type;
            }
        }

        foreach ($context->successfulEchoes as $echo) {
            $calledAe = mb_strtoupper($echo['called_ae']);
            foreach (self::TYPE_KEYWORDS as $keyword => $type) {
                if (str_contains($calledAe, $keyword)) {
                    return $type;
                }
            }
        }

        if ($context->successfulEchoes !== []) {
            return 'server';
        }

        return 'unbekannt';
    }
}
