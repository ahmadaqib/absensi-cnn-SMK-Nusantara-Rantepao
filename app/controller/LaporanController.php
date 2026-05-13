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
        $nama   = 'laporan-absensi-' . date('Ymd-His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nama . '"');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Tanggal', 'Jam', 'NIS', 'Nama', 'Kelas', 'Mata Pelajaran', 'Status', 'Confidence', 'Jarak Meter']);
        foreach ($data as $row) {
            fputcsv($out, [
                $row['tanggal'],
                $row['jam'],
                $row['nis'],
                $row['nama_siswa'],
                $row['nama_kelas'],
                $row['mata_pelajaran'],
                $row['status'],
                $row['confidence'],
                $row['jarak_dari_kelas'] ?? '',
            ]);
        }
        fclose($out);
        exit;
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
        echo '<table><thead><tr><th>Tanggal</th><th>Jam</th><th>NIS</th><th>Nama</th><th>Kelas</th><th>Mata Pelajaran</th><th>Status</th></tr></thead><tbody>';
        foreach ($data as $row) {
            echo '<tr><td>' . htmlspecialchars($row['tanggal']) . '</td><td>' . htmlspecialchars(substr($row['jam'], 0, 5)) . '</td><td>' . htmlspecialchars($row['nis']) . '</td><td>' . htmlspecialchars($row['nama_siswa']) . '</td><td>' . htmlspecialchars($row['nama_kelas']) . '</td><td>' . htmlspecialchars($row['mata_pelajaran']) . '</td><td>' . htmlspecialchars($row['status']) . '</td></tr>';
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
}
