<?php

namespace App\Services\Reports;

use Illuminate\Support\Collection;

final class TabularPdfExporter
{
    /**
     * @param  list<array{label: string, width: int}>  $columns
     * @param  iterable<int, list<string>>  $rows
     */
    public function export(string $title, string $subtitle, array $columns, iterable $rows): string
    {
        /** @var Collection<int, list<string>> $rowCollection */
        $rowCollection = collect($rows);
        $chunks = $rowCollection->chunk(13);
        if ($chunks->isEmpty()) {
            $chunks = collect([collect()]);
        }

        $objects = [1 => '<< /Type /Catalog /Pages 2 0 R >>', 2 => '', 3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>', 4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>'];
        $pageIds = [];
        foreach ($chunks as $pageNumber => $chunk) {
            $content = $this->page($title, $subtitle, $columns, $chunk, $pageNumber + 1, $chunks->count(), $rowCollection->count());
            $contentId = count($objects) + 1;
            $objects[$contentId] = '<< /Length '.strlen($content).">>\nstream\n{$content}\nendstream";
            $pageId = count($objects) + 1;
            $objects[$pageId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents {$contentId} 0 R >>";
            $pageIds[] = $pageId;
        }
        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', array_map(static fn (int $id): string => "{$id} 0 R", $pageIds)).'] /Count '.count($pageIds).' >>';

        return $this->document($objects);
    }

    /** @param list<array{label: string, width: int}> $columns
     * @param  Collection<int, list<string>>  $rows
     */
    private function page(string $title, string $subtitle, array $columns, Collection $rows, int $page, int $pages, int $total): string
    {
        $commands = ['0.08 0.18 0.35 rg', 'BT /F2 18 Tf 32 554 Td '.$this->text($title).' Tj ET', '0.35 0.4 0.5 rg', 'BT /F1 9 Tf 32 536 Td '.$this->text("{$subtitle} - {$total} Eintraege").' Tj ET', '0.92 0.95 0.98 rg 30 502 780 25 re f'];
        $x = 32;
        foreach ($columns as $column) {
            $commands[] = '0.1 0.2 0.35 rg BT /F2 8 Tf '.($x + 4).' 512 Td '.$this->text($column['label']).' Tj ET';
            $x += $column['width'];
        }

        $y = 470;
        foreach ($rows as $index => $row) {
            if ($index % 2 === 1) {
                $commands[] = '0.97 0.98 0.99 rg 30 '.($y - 8).' 780 32 re f';
            }
            $x = 32;
            foreach ($columns as $columnIndex => $column) {
                $length = max(5, (int) floor($column['width'] / 4.5));
                $commands[] = '0.12 0.16 0.22 rg BT /F1 7 Tf '.($x + 4).' '.$y.' Td '.$this->text($this->short($row[$columnIndex] ?? '', $length)).' Tj ET';
                $x += $column['width'];
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

    /** @param array<int, string> $objects */
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
