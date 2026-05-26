<?php

class DashboardController {

    private Absensi $absensiModel;
    private Siswa   $siswaModel;

    public function __construct() {
        Auth::cekLogin();
        $this->absensiModel = new Absensi();
        $this->siswaModel   = new Siswa();
    }

    public function index(): void {
        $ringkasan      = $this->absensiModel->ringkasanHariIni();
        $absensiTerbaru = $this->absensiModel->absensiHariIni(15);
        $dataGrafik     = $this->ambilDataGrafik();
        $judulHalaman   = 'Dashboard';

        require_once BASE_PATH . '/views/layouts/header.php';
        require_once BASE_PATH . '/views/layouts/sidebar.php';
        require_once BASE_PATH . '/views/dashboard/index.php';
        require_once BASE_PATH . '/views/layouts/footer.php';
    }

    // Siapkan data JSON untuk Chart.js
    private function ambilDataGrafik(): array {
        $tanggal = date('Y-m-d');
        $db      = koneksiDB();

        $stmt = $db->prepare(
            "SELECT k.nama AS nama_kelas,
                    COUNT(DISTINCT s.id) AS total,
                    COUNT(DISTINCT CASE WHEN a.status IN ('hadir','terlambat') THEN a.siswa_id END) AS hadir
             FROM kelas k
             JOIN siswa s ON s.kelas_id = k.id AND s.aktif = 1
             LEFT JOIN absensi a ON a.siswa_id = s.id AND a.tanggal = ?
             GROUP BY k.id, k.nama
             ORDER BY k.nama"
        );
        $stmt->execute([$tanggal]);
        $baris = $stmt->fetchAll();

        $label   = [];
        $hadir   = [];
        $absen   = [];
        foreach ($baris as $b) {
            $label[] = $b['nama_kelas'];
            $hadir[] = (int) $b['hadir'];
            $absen[] = (int) $b['total'] - (int) $b['hadir'];
        }

        return compact('label', 'hadir', 'absen');
    }
}
