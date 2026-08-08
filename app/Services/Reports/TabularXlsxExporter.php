<?php

namespace App\Services\Reports;

use RuntimeException;
use ZipArchive;

final class TabularXlsxExporter
{
    /**
     * @param  list<string>  $headers
     * @param  iterable<int, list<scalar|null>>  $rows
     */
    public function export(string $sheetName, array $headers, iterable $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'registry-xlsx-');
        if ($path === false) {
            throw new RuntimeException('Temporäre XLSX-Datei konnte nicht erstellt werden.');
        }

        $zip = new ZipArchive;
        $open = false;
        try {
            if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('XLSX-Archiv konnte nicht erstellt werden.');
            }
            $open = true;

            $zip->addFromString('[Content_Types].xml', $this->contentTypes());
            $zip->addFromString('_rels/.rels', $this->rootRelationships());
            $zip->addFromString('xl/workbook.xml', $this->workbook($sheetName));
            $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationships());
            $zip->addFromString('xl/styles.xml', $this->styles());
            $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheet($headers, $rows));
            $zip->close();
            $open = false;

            $contents = file_get_contents($path);
            if ($contents === false) {
                throw new RuntimeException('XLSX-Datei konnte nicht gelesen werden.');
            }

            return $contents;
        } finally {
            if ($open) {
                $zip->close();
            }
            @unlink($path);
        }
    }

    /** @param list<string> $headers
     * @param  iterable<int, list<scalar|null>>  $rows
     */
    private function sheet(array $headers, iterable $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><sheetData>';
        $xml .= $this->row(1, $headers, true);
        $index = 2;
        foreach ($rows as $row) {
            $xml .= $this->row($index++, $row, false);
        }

        return $xml.'</sheetData><autoFilter ref="A1:'.$this->column(count($headers)).max(1, $index - 1).'"/></worksheet>';
    }

    /** @param list<scalar|null> $values */
    private function row(int $index, array $values, bool $header): string
    {
        $cells = '';
        foreach ($values as $offset => $value) {
            $reference = $this->column($offset + 1).$index;
            $text = $value === null ? '' : (is_bool($value) ? ($value ? 'Ja' : 'Nein') : (string) $value);
            $cells .= '<c r="'.$reference.'" t="inlineStr"'.($header ? ' s="1"' : '').'><is><t xml:space="preserve">'.$this->xml($text).'</t></is></c>';
        }

        return '<row r="'.$index.'">'.$cells.'</row>';
    }

    private function column(int $index): string
    {
        $column = '';
        while ($index > 0) {
            $index--;
            $column = chr(65 + ($index % 26)).$column;
            $index = intdiv($index, 26);
        }

        return $column;
    }

    private function xml(string $value): string
    {
        $validXml = preg_replace('/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $value) ?? '';

        return htmlspecialchars($validXml, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function workbook(string $sheetName): string
    {
        $safeName = mb_substr(str_replace(['\\', '/', '?', '*', '[', ']', ':'], '-', $sheetName), 0, 31);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="'.$this->xml($safeName).'" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>';
    }

    private function rootRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
    }

    private function workbookRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="1"><fill><patternFill patternType="none"/></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs><cellXfs count="2"><xf fontId="0" fillId="0" borderId="0" xfId="0"/><xf fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs></styleSheet>';
    }
}
