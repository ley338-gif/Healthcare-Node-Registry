<?php

namespace App\Services\Reports;

use Illuminate\Support\Collection;

final class FirewallMatrixPdfExporter
{
    /** @param Collection<int,array<string,mixed>> $rows */
    public function export(Collection $rows): string
    {
        $chunks = $rows->chunk(13);
        if ($chunks->isEmpty()) {
            $chunks = collect([collect()]);
        }
        $objects = [1 => '<< /Type /Catalog /Pages 2 0 R >>', 2 => '', 3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>', 4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>'];
        $pageIds = [];
        foreach ($chunks as $pageNumber => $chunk) {
            $content = $this->page($chunk, $pageNumber + 1, $chunks->count(), $rows->count());
            $contentId = count($objects) + 1;
            $objects[$contentId] = '<< /Length '.strlen($content)." >>\nstream\n{$content}\nendstream";
            $pageId = count($objects) + 1;
            $objects[$pageId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents {$contentId} 0 R >>";
            $pageIds[] = $pageId;
        }
        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', array_map(static fn (int $id): string => "{$id} 0 R", $pageIds)).'] /Count '.count($pageIds).' >>';

        return $this->document($objects);
    }

    /** @param Collection<int,array<string,mixed>> $rows */
    private function page(Collection $rows, int $page, int $pages, int $total): string
    {
        $commands = ['0.08 0.18 0.35 rg', 'BT /F2 18 Tf 32 554 Td '.$this->text('Firewall- und Portmatrix').' Tj ET', '0.35 0.4 0.5 rg', 'BT /F1 9 Tf 32 536 Td '.$this->text("Aktive DICOM-Verbindungen - {$total} Eintraege").' Tj ET'];
        $columns = [['Kontext', 32, 125], ['Quelle', 157, 180], ['Ziel', 337, 180], ['Port', 517, 55], ['Dienst', 572, 90], ['TLS', 662, 45], ['AE Titles', 707, 103]];
        $commands[] = '0.92 0.95 0.98 rg 30 502 780 25 re f';
        foreach ($columns as [$label, $x]) {
            $commands[] = '0.1 0.2 0.35 rg BT /F2 8 Tf '.($x + 4).' 512 Td '.$this->text($label).' Tj ET';
        }
        $y = 470;
        foreach ($rows as $index => $row) {
            if ($index % 2 === 1) {
                $commands[] = '0.97 0.98 0.99 rg 30 '.($y - 8).' 780 32 re f';
            }
            $values = [
                $this->short(implode(' / ', array_filter([$row['source_organization'], $row['source_site'], $row['source_department']])).' > '.implode(' / ', array_filter([$row['target_organization'], $row['target_site'], $row['target_department']])), 27),
                $this->short($row['source_system'].' - '.$row['source_host'], 38),
                $this->short($row['target_system'].' - '.$row['target_host'], 38),
                (string) $row['port'], strtoupper((string) $row['service']), $row['tls_enabled'] ? 'Ja' : 'Nein',
                $this->short($row['source_ae_title'].' > '.$row['target_ae_title'], 23),
            ];
            foreach ($columns as $column => [, $x]) {
                $commands[] = '0.12 0.16 0.22 rg BT /F1 7 Tf '.($x + 4).' '.$y.' Td '.$this->text($values[$column]).' Tj ET';
            }
            $commands[] = '0.88 0.9 0.93 RG 30 '.($y - 10).' m 810 '.($y - 10).' l S';
            $y -= 34;
        }
        $commands[] = '0.4 0.45 0.52 rg BT /F1 8 Tf 700 22 Td '.$this->text("Seite {$page} von {$pages}").' Tj ET';

        return implode("\n", $commands);
    }

    private function text(string $value): string
    {
        $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT', $value) ?: $value;

        return '('.str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $encoded).')';
    }

    private function short(string $value, int $length): string
    {
        return mb_strlen($value) <= $length ? $value : mb_substr($value, 0, $length - 3).'...';
    }

    /** @param array<int,string> $objects */
    private function document(array $objects): string
    {
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$object}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= 'xref'."\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach (array_keys($objects) as $id) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$id])."\n";
        }
        $pdf .= 'trailer << /Size '.(count($objects) + 1).' /Root 1 0 R >>'."\nstartxref\n{$xref}\n%%EOF";

        return $pdf;
    }
}
