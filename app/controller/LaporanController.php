<?php

class LaporanController {

    private Absensi $absensiModel;
    private Kelas   $kelasModel;

    public function __construct() {
        Auth::cekRole(['admin', 'guru', 'kepala_sekolah']);
        $this->absensiModel = new Absensi();
        $this->kelasModel   = new Kelas();
    }

    public function index(): void {
        $filter       = $this->filterDariRequest();
        $daftarKelas  = $this->kelasModel->ambilSemua();
        $dataAbsensi  = $this->absensiModel->ambilDenganFilter($filter);
        $ringkasan    = $this->hitungRingkasan($dataAbsensi);
        $judulHalaman = 'Laporan Absensi';

        require_once BASE_PATH . '/views/layouts/header.php';
        require_once BASE_PATH . '/views/layouts/sidebar.php';
        require_once BASE_PATH . '/views/laporan/index.php';
        require_once BASE_PATH . '/views/layouts/footer.php';
    }

    public function exportExcel(): void {
        $filter = $this->filterDariRequest();
        $data   = $this->absensiModel->ambilDenganFilter($filter);
        $nama   = 'laporan-absensi-' . date('Ymd-His') . '.xlsx';
        $subjudul = 'Periode ' . date('d/m/Y', strtotime($filter['tanggal_dari'])) . ' sampai ' . date('d/m/Y', strtotime($filter['tanggal_sampai']));

        if (empty($filter['kelas_id'])) {
            XlsxWriter::kirimDownloadSheets($nama, $this->sheetsSemuaKelas($data, $subjudul));
            return;
        }

        XlsxWriter::kirimDownloadSheets($nama, [[
            'name' => 'Laporan',
            'title' => 'Laporan Absensi',
            'subtitle' => $subjudul,
            'summary' => $this->ringkasanUntukExcel($this->hitungRingkasan($data)),
            'header' => $this->headerExcel(),
            'rows' => $this->barisUntukExcel($data),
        ]]);
    }

    public function exportPdf(): void {
        $filter = $this->filterDariRequest();
        $data   = $this->absensiModel->ambilDenganFilter($filter);
        $judul  = 'Laporan Absensi';

        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html><head><meta charset="utf-8"><title>' . htmlspecialchars($judul) . '</title>';
        echo '<style>body{font-family:Arial,sans-serif;font-size:12px;color:#111827}table{width:100%;border-collapse:collapse}td,th{border:1px solid #d1d5db;padding:6px}th{background:#f3f4f6;text-align:left}.muted{color:#6b7280}</style>';
        echo '</head><body><h1>' . htmlspecialchars($judul) . '</h1>';
        echo '<p class="muted">Periode ' . htmlspecialchars($filter['tanggal_dari']) . ' sampai ' . htmlspecialchars($filter['tanggal_sampai']) . '</p>';
        echo '<table><thead><tr><th>Tanggal</th><th>Jam</th><th>NIS</th><th>Nama</th><th>Kelas</th><th>Mata Pelajaran</th><th>Status</th><th>Proses</th></tr></thead><tbody>';
        foreach ($data as $row) {
            echo '<tr><td>' . htmlspecialchars($row['tanggal']) . '</td><td>' . htmlspecialchars(substr($row['jam'], 0, 5)) . '</td><td>' . htmlspecialchars($row['nis']) . '</td><td>' . htmlspecialchars($row['nama_siswa']) . '</td><td>' . htmlspecialchars($row['nama_kelas']) . '</td><td>' . htmlspecialchars($row['mata_pelajaran']) . '</td><td>' . htmlspecialchars($row['status']) . '</td><td>' . htmlspecialchars($this->labelProses($row['status_antrian'] ?? 'FINAL')) . '</td></tr>';
        }
        echo '</tbody></table><script>window.print();</script></body></html>';
        exit;
    }

    private function filterDariRequest(): array {
        return [
            'kelas_id'       => (int) ($_GET['kelas_id'] ?? 0),
            'jadwal_id'      => (int) ($_GET['jadwal_id'] ?? 0),
            'tanggal_dari'   => $_GET['tanggal_dari']   ?? date('Y-m-d'),
            'tanggal_sampai' => $_GET['tanggal_sampai'] ?? date('Y-m-d'),
            'status'         => $_GET['status'] ?? '',
        ];
    }

    private function hitungRingkasan(array $data): array {
        $ringkasan = ['hadir' => 0, 'terlambat' => 0, 'tidak_hadir' => 0, 'total' => count($data)];
        foreach ($data as $row) {
            if (isset($ringkasan[$row['status']])) {
                $ringkasan[$row['status']]++;
            }
        }
        return $ringkasan;
    }

    private function labelProses(string $status): string {
        return [
            'FINAL'      => 'Tersimpan',
            'DONE'       => 'Tersimpan',
            'PENDING'    => 'Menunggu RPA',
            'PROCESSING' => 'Diproses RPA',
        ][$status] ?? $status;
    }

    private function labelStatus(string $status): string {
        return [
            'hadir'       => 'Hadir',
            'terlambat'   => 'Terlambat',
            'tidak_hadir' => 'Tidak Hadir',
        ][$status] ?? ucwords(str_replace('_', ' ', $status));
    }

    private function ringkasanUntukExcel(array $ringkasan): array {
        return [
            'Total' => (int) $ringkasan['total'],
            'Hadir' => (int) $ringkasan['hadir'],
            'Terlambat' => (int) $ringkasan['terlambat'],
            'Tidak Hadir' => (int) $ringkasan['tidak_hadir'],
        ];
    }

    private function headerExcel(): array {
        return ['Tanggal', 'Jam', 'NIS', 'Nama Siswa', 'Kelas', 'Mata Pelajaran', 'Status', 'Proses', 'Confidence', 'Jarak'];
    }

    private function sheetsSemuaKelas(array $data, string $subjudul): array {
        $sheets = [[
            'name' => 'Ringkasan',
            'title' => 'Ringkasan Laporan Absensi',
            'subtitle' => $subjudul,
            'summary' => $this->ringkasanUntukExcel($this->hitungRingkasan($data)),
            'header' => ['Kelas', 'Total', 'Hadir', 'Terlambat', 'Tidak Hadir'],
            'rows' => $this->barisRingkasanPerKelas($data),
        ]];

        foreach ($this->kelompokkanPerKelas($data) as $namaKelas => $rows) {
            $sheets[] = [
                'name' => $namaKelas,
                'title' => 'Laporan Absensi - ' . $namaKelas,
                'subtitle' => $subjudul,
                'summary' => $this->ringkasanUntukExcel($this->hitungRingkasan($rows)),
                'header' => $this->headerExcel(),
                'rows' => $this->barisUntukExcel($rows),
            ];
        }

        return $sheets;
    }

    private function kelompokkanPerKelas(array $data): array {
        $hasil = [];
        foreach ($data as $row) {
            $kelas = $row['nama_kelas'] ?: 'Tanpa Kelas';
            $hasil[$kelas][] = $row;
        }
        ksort($hasil, SORT_NATURAL | SORT_FLAG_CASE);
        return $hasil;
    }

    private function barisRingkasanPerKelas(array $data): array {
        $rows = [];
        foreach ($this->kelompokkanPerKelas($data) as $namaKelas => $items) {
            $r = $this->hitungRingkasan($items);
            $rows[] = [
                $namaKelas,
                (int) $r['total'],
                (int) $r['hadir'],
                (int) $r['terlambat'],
                (int) $r['tidak_hadir'],
            ];
        }
        return $rows;
    }

    private function barisUntukExcel(array $data): array {
        return array_map(function (array $row): array {
            return [
                date('d/m/Y', strtotime($row['tanggal'])),
                substr($row['jam'], 0, 5),
                $row['nis'],
                $row['nama_siswa'],
                $row['nama_kelas'],
                $row['mata_pelajaran'],
                $this->labelStatus($row['status']),
                $this->labelProses($row['status_antrian'] ?? 'FINAL'),
                $row['confidence'] !== null ? number_format((float) $row['confidence'] * 100, 1) . '%' : '-',
                $row['jarak_dari_kelas'] !== null ? number_format((float) $row['jarak_dari_kelas'], 1) . ' m' : '-',
            ];
        }, $data);
    }
}
