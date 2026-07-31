<?php

namespace App\Services\Diagnostics;

use DOMDocument;
use DOMElement;
use DOMXPath;

final class PacsQueryResponseParser
{
    /** @return list<array<string, int|string|null>> */
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
            throw new \UnexpectedValueException('Ungültige XML-Antwort.');
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
                'patientName' => $tags['00100010'] ?? null, 'patientId' => $tags['00100020'] ?? null,
                'accessionNumber' => $tags['00080050'] ?? null, 'studyInstanceUid' => $tags['0020000d'] ?? null,
                'studyDate' => $tags['00080020'] ?? null, 'modality' => $tags['00080061'] ?? null,
                'studyDescription' => $tags['00081030'] ?? null,
                'seriesCount' => isset($tags['00201206']) && ctype_digit($tags['00201206']) ? (int) $tags['00201206'] : null,
                'instanceCount' => isset($tags['00201208']) && ctype_digit($tags['00201208']) ? (int) $tags['00201208'] : null,
            ];
        }

        return array_slice($results, 0, 100);
    }
}
