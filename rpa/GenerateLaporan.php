<?php

/**
 * UiPath Workflow: GenerateLaporan
 * Membuat laporan harian sederhana agar scheduler punya artefak yang bisa diaudit.
 */
class GenerateLaporan {

    private PDO       $db;
    private UiPathBot $bot;
    private string    $dirLaporan;

    public function __construct(PDO $db, UiPathBot $bot) {
        $this->db         = $db;
        $this->bot        = $bot;
        $this->dirLaporan = __DIR__ . '/laporan';
        if (!is_dir($this->dirLaporan)) {
            mkdir($this->dirLaporan, 0755, true);
        }
    }

    public function cekDanGenerate(): void {
        $tanggal = date('Y-m-d');
        if (date('H:i') < '17:00') {
            return;
        }

        $this->generateJikaBelumAda($tanggal, 'excel');
        $this->generateJikaBelumAda($tanggal, 'pdf');
    }

    private function generateJikaBelumAda(string $tanggal, string $format): void {
        if ($this->sudahAda($tanggal, $format)) {
            return;
        }

        $data = $this->ambilDataHarian($tanggal);
        $path = $format === 'excel'
            ? $this->tulisXlsx($tanggal, $data)
            : $this->tulisHtmlPdf($tanggal, $data);

        $this->db->prepare(
            "INSERT INTO laporan_tersedia (tipe, periode, path_file, format) VALUES ('harian', ?, ?, ?)"
        )->execute([$tanggal, $path, $format]);

        $this->bot->log("GenerateLaporan: laporan $format periode $tanggal dibuat.");
    }

    private function sudahAda(string $tanggal, string $format): bool {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM laporan_tersedia
             WHERE tipe='harian' AND periode=? AND format=?"
        );
        $stmt->execute([$tanggal, $format]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function ambilDataHarian(string $tanggal): array {
        $stmt = $this->db->prepare(
            "SELECT a.tanggal, a.jam, a.status, a.confidence,
                    s.nis, s.nama AS nama_siswa,
                    k.nama AS nama_kelas,
                    j.mata_pelajaran
             FROM absensi a
             JOIN siswa s ON s.id = a.siswa_id
             JOIN kelas k ON k.id = s.kelas_id
             JOIN jadwal j ON j.id = a.jadwal_id
             WHERE a.tanggal = ?
             ORDER BY k.nama, s.nama, a.jam"
        );
        $stmt->execute([$tanggal]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function tulisXlsx(string $tanggal, array $data): string {
        $path = $this->dirLaporan . "/laporan-harian-$tanggal.xlsx";
        XlsxWriter::tulisFile(
            $path,
            'Laporan Absensi Harian',
            'Tanggal ' . date('d/m/Y', strtotime($tanggal)),
            $this->ringkasan($data),
            ['Tanggal', 'Jam', 'NIS', 'Nama Siswa', 'Kelas', 'Mata Pelajaran', 'Status', 'Proses', 'Confidence'],
            array_map(fn(array $row) => [
                date('d/m/Y', strtotime($row['tanggal'])),
                substr($row['jam'], 0, 5),
                $row['nis'],
                $row['nama_siswa'],
                $row['nama_kelas'],
                $row['mata_pelajaran'],
                $this->labelStatus($row['status']),
                'Tersimpan',
                $row['confidence'] !== null ? number_format((float) $row['confidence'] * 100, 1) . '%' : '-',
            ], $data)
        );
        return $path;
    }

    private function tulisHtmlPdf(string $tanggal, array $data): string {
        $path = $this->dirLaporan . "/laporan-harian-$tanggal.html";
        $html = "<!doctype html><html><head><meta charset=\"utf-8\"><title>Laporan $tanggal</title>";
        $html .= "<style>body{font-family:Arial,sans-serif;font-size:12px}table{width:100%;border-collapse:collapse}td,th{border:1px solid #ddd;padding:6px}th{background:#f1f5f9;text-align:left}</style>";
        $html .= "</head><body><h1>Laporan Absensi Harian $tanggal</h1><table><thead><tr><th>Tanggal</th><th>Jam</th><th>NIS</th><th>Nama</th><th>Kelas</th><th>Mapel</th><th>Status</th></tr></thead><tbody>";
        foreach ($data as $row) {
            $html .= '<tr><td>' . htmlspecialchars($row['tanggal']) . '</td><td>' . htmlspecialchars($row['jam']) . '</td><td>' . htmlspecialchars($row['nis']) . '</td><td>' . htmlspecialchars($row['nama_siswa']) . '</td><td>' . htmlspecialchars($row['nama_kelas']) . '</td><td>' . htmlspecialchars($row['mata_pelajaran']) . '</td><td>' . htmlspecialchars($row['status']) . '</td></tr>';
        }
        $html .= '</tbody></table></body></html>';
        file_put_contents($path, $html);
        return $path;
    }

    private function ringkasan(array $data): array {
        $hasil = ['Total' => count($data), 'Hadir' => 0, 'Terlambat' => 0, 'Tidak Hadir' => 0];
        foreach ($data as $row) {
            if ($row['status'] === 'hadir') $hasil['Hadir']++;
            if ($row['status'] === 'terlambat') $hasil['Terlambat']++;
            if ($row['status'] === 'tidak_hadir') $hasil['Tidak Hadir']++;
        }
        return $hasil;
    }

    private function labelStatus(string $status): string {
        return [
            'hadir'       => 'Hadir',
            'terlambat'   => 'Terlambat',
            'tidak_hadir' => 'Tidak Hadir',
        ][$status] ?? ucwords(str_replace('_', ' ', $status));
    }
}
