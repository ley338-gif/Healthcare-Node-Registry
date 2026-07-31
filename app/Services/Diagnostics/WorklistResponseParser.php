<?php

namespace App\Services\Diagnostics;

use DOMDocument;
use DOMElement;
use DOMXPath;

final class WorklistResponseParser
{
    /** @return list<array<string, string|null>> */
    public function parse(string $xml): array
    {
        if (trim($xml) === '') {
            return [];
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            throw new \UnexpectedValueException('Ungültige XML-Antwort von findscu.');
        }

        $xpath = new DOMXPath($document);
        $datasets = $xpath->query('//*[local-name()="data-set" and not(ancestor::*[local-name()="data-set"])]');
        $results = [];

        if ($datasets === false) {
            return [];
        }

        foreach ($datasets as $dataset) {
            if (! $dataset instanceof DOMElement) {
                continue;
            }

            $tags = [];
            $elements = $xpath->query('.//*[@tag]', $dataset);

            if ($elements === false) {
                continue;
            }

            foreach ($elements as $element) {
                if ($element instanceof DOMElement) {
                    $tags[strtolower(str_replace(',', '', $element->getAttribute('tag')))] = trim($element->textContent);
                }
            }

            $results[] = [
                'patientName' => $tags['00100010'] ?? null,
                'patientId' => $tags['00100020'] ?? null,
                'patientBirthDate' => $tags['00100030'] ?? null,
                'patientSex' => $tags['00100040'] ?? null,
                'accessionNumber' => $tags['00080050'] ?? null,
                'requestedProcedureId' => $tags['00401001'] ?? null,
                'requestedProcedureDescription' => $tags['00321060'] ?? null,
                'studyInstanceUid' => $tags['0020000d'] ?? null,
                'scheduledStationAeTitle' => $tags['00400001'] ?? null,
                'scheduledDate' => $tags['00400002'] ?? null,
                'scheduledTime' => $tags['00400003'] ?? null,
                'modality' => $tags['00080060'] ?? null,
                'scheduledDescription' => $tags['00400007'] ?? null,
                'scheduledProcedureStepId' => $tags['00400009'] ?? null,
            ];
        }

        return array_slice($results, 0, 100);
    }
}
