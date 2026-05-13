<?php

class XlsxWriter {

    public static function kirimDownload(string $namaFile, string $judul, string $subjudul, array $ringkasan, array $header, array $rows): void {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
        self::tulisFile($tmp, $judul, $subjudul, $ringkasan, $header, $rows);
        self::downloadFile($tmp, $namaFile);
    }

    public static function kirimDownloadSheets(string $namaFile, array $sheets): void {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
        self::tulisFileSheets($tmp, $sheets);
        self::downloadFile($tmp, $namaFile);
    }

    private static function downloadFile(string $path, string $namaFile): void {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $namaFile . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: max-age=0');

        readfile($path);
        unlink($path);
        exit;
    }

    public static function tulisFile(string $path, string $judul, string $subjudul, array $ringkasan, array $header, array $rows): void {
        self::tulisFileSheets($path, [[
            'name' => 'Laporan Absensi',
            'title' => $judul,
            'subtitle' => $subjudul,
            'summary' => $ringkasan,
            'header' => $header,
            'rows' => $rows,
        ]]);
    }

    public static function tulisFileSheets(string $path, array $sheets): void {
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('Ekstensi PHP ZipArchive belum aktif.');
        }
        if (empty($sheets)) {
            throw new InvalidArgumentException('Minimal satu sheet harus disediakan.');
        }

        $sheets = self::normalisasiSheets($sheets);

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Gagal membuat file XLSX.');
        }

        $zip->addFromString('[Content_Types].xml', self::contentTypes(count($sheets)));
        $zip->addFromString('_rels/.rels', self::rels());
        $zip->addFromString('xl/workbook.xml', self::workbook($sheets));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRels(count($sheets)));
        $zip->addFromString('xl/styles.xml', self::styles());
        foreach ($sheets as $i => $sheet) {
            $zip->addFromString(
                'xl/worksheets/sheet' . ($i + 1) . '.xml',
                self::sheet($sheet['title'], $sheet['subtitle'], $sheet['summary'], $sheet['header'], $sheet['rows'])
            );
        }
        $zip->addFromString('docProps/core.xml', self::coreProps());
        $zip->addFromString('docProps/app.xml', self::appProps(count($sheets)));
        $zip->close();
    }

    private static function normalisasiSheets(array $sheets): array {
        $hasil = [];
        $namaTerpakai = [];
        foreach ($sheets as $i => $sheet) {
            $nama = self::namaSheet((string) ($sheet['name'] ?? 'Sheet ' . ($i + 1)));
            $namaDasar = $nama;
            $counter = 2;
            while (isset($namaTerpakai[strtolower($nama)])) {
                $suffix = ' ' . $counter;
                $nama = substr($namaDasar, 0, 31 - strlen($suffix)) . $suffix;
                $counter++;
            }
            $namaTerpakai[strtolower($nama)] = true;

            $hasil[] = [
                'name' => $nama,
                'title' => (string) ($sheet['title'] ?? $nama),
                'subtitle' => (string) ($sheet['subtitle'] ?? ''),
                'summary' => $sheet['summary'] ?? [],
                'header' => $sheet['header'] ?? [],
                'rows' => $sheet['rows'] ?? [],
            ];
        }
        return $hasil;
    }

    private static function sheet(string $judul, string $subjudul, array $ringkasan, array $header, array $rows): string {
        $jumlahKolom = count($header);
        $kolomAkhir = self::kolom($jumlahKolom);
        $xmlRows = [];

        $xmlRows[] = self::row(1, [self::cell('A1', $judul, 's', 1)]);
        $xmlRows[] = self::row(2, [self::cell('A2', $subjudul, 's', 2)]);

        $summaryLabels = [];
        $summaryValues = [];
        foreach (array_slice($ringkasan, 0, 4, true) as $label => $nilai) {
            $col = count($summaryLabels) + 1;
            $summaryLabels[] = self::cell(self::kolom($col) . '4', (string) $label, 's', 3);
            $summaryValues[] = self::cell(self::kolom($col) . '5', (string) $nilai, 'n', 4);
        }
        if ($summaryLabels) {
            $xmlRows[] = self::row(4, $summaryLabels);
            $xmlRows[] = self::row(5, $summaryValues);
        }

        $headerCells = [];
        foreach ($header as $i => $nama) {
            $headerCells[] = self::cell(self::kolom($i + 1) . '7', $nama, 's', 5);
        }
        $xmlRows[] = self::row(7, $headerCells);

        $rowNum = 8;
        foreach ($rows as $dataRow) {
            $cells = [];
            foreach ($dataRow as $i => $value) {
                $style = $rowNum % 2 === 0 ? 6 : 7;
                if (is_int($value) || is_float($value)) {
                    $cells[] = self::cell(self::kolom($i + 1) . $rowNum, (string) $value, 'n', $style);
                } else {
                    $cells[] = self::cell(self::kolom($i + 1) . $rowNum, (string) $value, 's', $style);
                }
            }
            $xmlRows[] = self::row($rowNum, $cells);
            $rowNum++;
        }

        $mergeCells = '<mergeCells count="2"><mergeCell ref="A1:' . $kolomAkhir . '1"/><mergeCell ref="A2:' . $kolomAkhir . '2"/></mergeCells>';
        $autoFilter = '<autoFilter ref="A7:' . $kolomAkhir . max(7, $rowNum - 1) . '"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<dimension ref="A1:' . $kolomAkhir . max(8, $rowNum - 1) . '"/>'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="7" topLeftCell="A8" activePane="bottomLeft" state="frozen"/><selection pane="bottomLeft" activeCell="A8" sqref="A8"/></sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="18"/>'
            . self::cols($jumlahKolom)
            . '<sheetData>' . implode('', $xmlRows) . '</sheetData>'
            . $autoFilter
            . $mergeCells
            . '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.3" footer="0.3"/>'
            . '</worksheet>';
    }

    private static function cols(int $jumlahKolom): string {
        $widths = [14, 10, 14, 28, 16, 26, 14, 16, 12, 14];
        $cols = '<cols>';
        for ($i = 1; $i <= $jumlahKolom; $i++) {
            $width = $widths[$i - 1] ?? 16;
            $cols .= '<col min="' . $i . '" max="' . $i . '" width="' . $width . '" customWidth="1"/>';
        }
        return $cols . '</cols>';
    }

    private static function row(int $num, array $cells): string {
        return '<row r="' . $num . '">' . implode('', $cells) . '</row>';
    }

    private static function cell(string $ref, string $value, string $type, int $style): string {
        if ($type === 'n' && is_numeric($value)) {
            return '<c r="' . $ref . '" s="' . $style . '"><v>' . $value . '</v></c>';
        }

        return '<c r="' . $ref . '" t="inlineStr" s="' . $style . '"><is><t>' . self::e($value) . '</t></is></c>';
    }

    private static function kolom(int $index): string {
        $nama = '';
        while ($index > 0) {
            $index--;
            $nama = chr(65 + ($index % 26)) . $nama;
            $index = intdiv($index, 26);
        }
        return $nama;
    }

    private static function e(string $value): string {
        $value = preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $value) ?? '';
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function styles(): string {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="4"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="16"/><color rgb="FF111827"/><name val="Calibri"/></font><font><sz val="11"/><color rgb="FF64748B"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
            . '<fills count="5"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF1E40AF"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFEFF6FF"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFE2E8F0"/></left><right style="thin"><color rgb="FFE2E8F0"/></right><top style="thin"><color rgb="FFE2E8F0"/></top><bottom style="thin"><color rgb="FFE2E8F0"/></bottom><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="8">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            . '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center"/></xf>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1"><alignment horizontal="center"/></xf>'
            . '<xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>'
            . '<xf numFmtId="0" fontId="0" fillId="4" borderId="1" xfId="0" applyFill="1" applyBorder="1"/>'
            . '</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }

    private static function contentTypes(int $jumlahSheet): string {
        $worksheets = '';
        for ($i = 1; $i <= $jumlahSheet; $i++) {
            $worksheets .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . $worksheets
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>';
    }

    private static function rels(): string {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>';
    }

    private static function workbook(array $sheets): string {
        $xmlSheets = '';
        foreach ($sheets as $i => $sheet) {
            $id = $i + 1;
            $xmlSheets .= '<sheet name="' . self::e($sheet['name']) . '" sheetId="' . $id . '" r:id="rId' . $id . '"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>' . $xmlSheets . '</sheets></workbook>';
    }

    private static function workbookRels(int $jumlahSheet): string {
        $rels = '';
        for ($i = 1; $i <= $jumlahSheet; $i++) {
            $rels .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
        }
        $rels .= '<Relationship Id="rId' . ($jumlahSheet + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $rels . '</Relationships>';
    }

    private static function coreProps(): string {
        $now = gmdate('Y-m-d\TH:i:s\Z');
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:creator>Sistem Absensi CNN</dc:creator><cp:lastModifiedBy>Sistem Absensi CNN</cp:lastModifiedBy><dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:modified></cp:coreProperties>';
    }

    private static function appProps(int $jumlahSheet): string {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>Sistem Absensi CNN</Application><HeadingPairs><vt:vector size="2" baseType="variant"><vt:variant><vt:lpstr>Worksheets</vt:lpstr></vt:variant><vt:variant><vt:i4>' . $jumlahSheet . '</vt:i4></vt:variant></vt:vector></HeadingPairs></Properties>';
    }

    private static function namaSheet(string $nama): string {
        $nama = preg_replace('/[\[\]\:\*\?\/\\\\]/', ' ', $nama) ?? 'Sheet';
        $nama = trim(preg_replace('/\s+/', ' ', $nama) ?? 'Sheet');
        if ($nama === '') {
            $nama = 'Sheet';
        }
        return mb_substr($nama, 0, 31);
    }
}
