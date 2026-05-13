<?php

class AbsensiController {

    private Absensi    $absensiModel;
    private Siswa      $siswaModel;
    private Jadwal     $jadwalModel;
    private CNNService $cnn;

    public function __construct() {
        Auth::cekLogin();
        $this->absensiModel = new Absensi();
        $this->siswaModel   = new Siswa();
        $this->jadwalModel  = new Jadwal();
        $this->cnn          = new CNNService();
    }

    // Halaman kamera absensi (akses: semua role yang login)
    public function kamera(): void {
        // Ambil jadwal hari ini untuk semua kelas — guru/admin memilih kelas
        $daftarKelas  = (new Kelas())->ambilSemua();
        $kelasId      = (int) ($_GET['kelas_id'] ?? ($daftarKelas[0]['id'] ?? 0));
        $jadwalHariIni = $this->jadwalModel->ambilHariIni($kelasId);
        $jadwalId     = (int) ($_GET['jadwal_id'] ?? ($jadwalHariIni[0]['id'] ?? 0));
        $statusCnn    = $this->cnn->cekStatus();
        $judulHalaman = 'Absensi Kamera';

        require_once BASE_PATH . '/views/layouts/header.php';
        require_once BASE_PATH . '/views/layouts/sidebar.php';
        require_once BASE_PATH . '/views/absensi/kamera.php';
        require_once BASE_PATH . '/views/layouts/footer.php';
    }

    // AJAX endpoint — dipanggil JS setiap 2 detik
    public function proses(): void {
        $gambar   = $_POST['gambar']   ?? '';
        $jadwalId = (int) ($_POST['jadwal_id'] ?? 0);

        if (!$gambar || !$jadwalId) {
            Response::json(['status' => 'error', 'pesan' => 'Data tidak lengkap.'], 400);
            return;
        }

        // Kirim ke CNN service
        $hasilCnn = $this->cnn->kenaliWajah($gambar);

        if ($hasilCnn === null) {
            Response::json(['status' => 'error', 'pesan' => 'CNN service tidak dapat dihubungi. Pastikan Python berjalan.']);
            return;
        }

        if ($hasilCnn['status'] === 'error') {
            Response::json($hasilCnn);
            return;
        }

        if ($hasilCnn['status'] === 'gagal') {
            Response::json($hasilCnn);
            return;
        }

        // Wajah dikenali — cari siswa berdasarkan NIS
        $nis   = $hasilCnn['nis'] ?? '';
        $siswa = $this->siswaModel->cariByNis($nis);

        if (!$siswa) {
            Response::json(['status' => 'error', 'pesan' => "NIS $nis tidak ditemukan di database."]);
            return;
        }

        $tanggal = date('Y-m-d');
        $jam     = date('H:i:s');

        // Cek sudah absen
        if ($this->absensiModel->sudahAbsen($siswa['id'], $jadwalId, $tanggal)) {
            Response::json([
                'status'      => 'duplikat',
                'nama_siswa'  => $siswa['nama'],
                'nis'         => $nis,
                'confidence'  => $hasilCnn['confidence'],
                'pesan'       => $siswa['nama'] . ' sudah absen hari ini.',
            ]);
            return;
        }

        // Tentukan status hadir/terlambat
        $statusAbsensi = $this->tentukanStatus($jadwalId, $jam);

        $this->absensiModel->simpan([
            'siswa_id'   => $siswa['id'],
            'jadwal_id'  => $jadwalId,
            'tanggal'    => $tanggal,
            'jam'        => $jam,
            'status'     => $statusAbsensi,
            'confidence' => $hasilCnn['confidence'],
        ]);

        Response::json([
            'status'         => 'berhasil',
            'nama_siswa'     => $siswa['nama'],
            'nis'            => $nis,
            'jam'            => substr($jam, 0, 5),
            'status_absensi' => $statusAbsensi,
            'confidence'     => $hasilCnn['confidence'],
            'pesan'          => 'Absensi ' . $siswa['nama'] . ' berhasil dicatat.',
        ]);
    }

    // Halaman rekap absensi
    public function rekap(): void {
        Auth::cekRole(['admin', 'guru', 'kepala_sekolah']);
        $daftarKelas  = (new Kelas())->ambilSemua();
        $judulHalaman = 'Rekap Absensi';

        // Filter default: hari ini
        $filter = [
            'kelas_id'      => (int) ($_GET['kelas_id'] ?? 0),
            'tanggal_dari'  => $_GET['tanggal_dari']  ?? date('Y-m-d'),
            'tanggal_sampai'=> $_GET['tanggal_sampai'] ?? date('Y-m-d'),
            'status'        => $_GET['status'] ?? '',
        ];
        $dataAbsensi = $this->absensiModel->ambilDenganFilter($filter);

        require_once BASE_PATH . '/views/layouts/header.php';
        require_once BASE_PATH . '/views/layouts/sidebar.php';
        require_once BASE_PATH . '/views/absensi/rekap.php';
        require_once BASE_PATH . '/views/layouts/footer.php';
    }

    // AJAX — data rekap terbaru untuk polling dashboard
    public function rekapData(): void {
        $terbaru = $this->absensiModel->absensiHariIni(10);
        Response::json(['data' => $terbaru]);
    }

    private function tentukanStatus(int $jadwalId, string $jam): string {
        $jadwal = $this->jadwalModel->cariById($jadwalId);
        if (!$jadwal) return 'hadir';

        $selisihMenit = (strtotime($jam) - strtotime($jadwal['jam_mulai'])) / 60;
        return $selisihMenit > TOLERANSI_TERLAMBAT ? 'terlambat' : 'hadir';
    }
}
