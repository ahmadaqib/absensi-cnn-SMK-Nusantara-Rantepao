<?php

class AbsensiGuruController {

    private AbsensiGuru $absensiGuruModel;
    private Jadwal      $jadwalModel;
    private Kelas       $kelasModel;
    private Pengguna    $penggunaModel;
    private CNNService  $cnn;

    public function __construct() {
        Auth::cekLogin();
        $this->absensiGuruModel = new AbsensiGuru();
        $this->jadwalModel      = new Jadwal();
        $this->kelasModel       = new Kelas();
        $this->penggunaModel    = new Pengguna();
        $this->cnn              = new CNNService();
    }

    public function index(): void {
        Auth::cekRole(['guru']);
        $guruId          = Auth::idSaatIni() ?? 0;
        $jadwalHariIni   = $this->jadwalModel->ambilHariIniGuru($guruId);
        $absensiHariIni  = $this->absensiGuruModel->ambilHariIniGuru($guruId);
        $judulHalaman    = 'Absensi Guru';

        require_once BASE_PATH . '/views/layouts/header.php';
        require_once BASE_PATH . '/views/layouts/sidebar.php';
        require_once BASE_PATH . '/views/absensi_guru/index.php';
        require_once BASE_PATH . '/views/layouts/footer.php';
    }

    public function simpan(): void {
        Auth::cekRole(['guru']);
        $guruId   = Auth::idSaatIni() ?? 0;
        $jadwalId = (int) ($_POST['jadwal_id'] ?? 0);
        $latGuru  = $this->ambilKoordinatPost('latitude');
        $lonGuru  = $this->ambilKoordinatPost('longitude');
        $gambar   = $_POST['gambar'] ?? '';

        if (!$gambar || !$jadwalId) {
            Response::json(['status' => 'gagal', 'pesan' => 'Data tidak lengkap.']);
            return;
        }

        $guru = $this->penggunaModel->cariById($guruId);
        if (!$guru) {
            Response::json(['status' => 'gagal', 'pesan' => 'Guru tidak ditemukan.']);
            return;
        }

        $jadwal = $this->jadwalModel->cariById($jadwalId);
        if (!$jadwal || (int) $jadwal['guru_id'] !== $guruId) {
            Response::json(['status' => 'gagal', 'pesan' => 'Jadwal tidak ditemukan untuk akun guru ini.']);
            return;
        }

        $tanggal = date('Y-m-d');
        if ($this->absensiGuruModel->sudahAbsen($guruId, $jadwalId, $tanggal)) {
            Response::json(['status' => 'gagal', 'pesan' => 'Kehadiran untuk jadwal ini sudah dicatat hari ini.']);
            return;
        }

        // ── CNN service ──
        $hasilCnn = $this->cnn->kenaliWajah($gambar);

        if ($hasilCnn === null) {
            $detail = $this->cnn->getLastError();
            Response::json([
                'status' => 'gagal',
                'pesan'  => 'CNN service aktif, tetapi endpoint pengenalan wajah tidak merespons'
                    . ($detail ? ": $detail" : '. Coba ulangi atau restart Python service.'),
            ]);
            return;
        }

        if ($hasilCnn['status'] !== 'berhasil') {
            Response::json(['status' => 'gagal', 'pesan' => $hasilCnn['pesan'] ?? 'Wajah tidak dikenali.']);
            return;
        }

        // Cocokkan username hasil klasifikasi dengan username guru yang login
        $usernameDikenali = $hasilCnn['nis'] ?? '';
        if (strtolower($usernameDikenali) !== strtolower($guru['username'])) {
            Response::json([
                'status' => 'gagal',
                'pesan'  => 'Wajah terdeteksi sebagai pengguna lain (' . htmlspecialchars($usernameDikenali) . '). Silakan posisikan wajah Anda dengan benar.',
            ]);
            return;
        }

        $koordinat = $this->kelasModel->ambilKoordinat((int) $jadwal['kelas_id']);
        if ($koordinat && !empty($koordinat['latitude'])) {
            if ($latGuru === null || $lonGuru === null) {
                Response::json(['status' => 'gagal', 'pesan' => 'Lokasi GPS belum terbaca. Izinkan lokasi browser lalu coba lagi.']);
                return;
            }

            $jarak = $this->hitungHaversine(
                $latGuru,
                $lonGuru,
                (float) $koordinat['latitude'],
                (float) $koordinat['longitude']
            );
            $radiusMaks = (int) ($koordinat['radius'] ?? RADIUS_MAKSIMAL);

            if ($jarak > $radiusMaks) {
                Response::json([
                    'status' => 'gagal',
                    'pesan'  => sprintf('Di luar area kelas (%.0f m dari kelas, maks %d m).', $jarak, $radiusMaks)
                ]);
                return;
            }
        }

        $jam = date('H:i:s');
        $tersimpan = $this->absensiGuruModel->simpan([
            'guru_id'            => $guruId,
            'jadwal_id'          => $jadwalId,
            'tanggal'            => $tanggal,
            'jam'                => $jam,
            'status'             => $this->tentukanStatus($jadwal, $jam),
            'latitude_absensi'   => $latGuru,
            'longitude_absensi'  => $lonGuru,
            'jarak_dari_kelas'   => isset($jarak) ? round($jarak, 2) : null,
        ]);

        if (!$tersimpan) {
            Response::json(['status' => 'gagal', 'pesan' => 'Kehadiran guru gagal disimpan.']);
            return;
        }

        Response::json(['status' => 'sukses', 'pesan' => 'Kehadiran guru berhasil dicatat.']);
    }

    public function dataset(): void {
        Auth::cekRole(['guru']);
        $guruId = Auth::idSaatIni() ?? 0;
        $guru   = $this->penggunaModel->cariById($guruId);

        if (!$guru) {
            Response::redirectDenganPesan('dashboard', 'gagal', 'Data guru tidak ditemukan.');
            return;
        }

        $dirDataset   = BASE_PATH . '/python/dataset/' . $guru['username'] . '/';
        $jumlahFoto   = is_dir($dirDataset) ? count(glob($dirDataset . '*.jpg')) : 0;
        $judulHalaman = 'Dataset Wajah — ' . $guru['nama'];

        require_once BASE_PATH . '/views/layouts/header.php';
        require_once BASE_PATH . '/views/layouts/sidebar.php';
        require_once BASE_PATH . '/views/absensi_guru/dataset.php';
        require_once BASE_PATH . '/views/layouts/footer.php';
    }

    public function simpanDataset(): void {
        header('Content-Type: application/json');
        Auth::cekRole(['guru']);
        $guruId = Auth::idSaatIni() ?? 0;
        $guru   = $this->penggunaModel->cariById($guruId);

        if (!$guru) {
            Response::json(['status' => 'error', 'pesan' => 'Guru tidak ditemukan.'], 404);
            return;
        }

        $base64 = $_POST['gambar'] ?? '';
        if (!preg_match('/^data:image\/jpeg;base64,/', $base64)) {
            Response::json(['status' => 'error', 'pesan' => 'Format gambar tidak valid.'], 400);
            return;
        }

        $dirDataset = BASE_PATH . '/python/dataset/' . $guru['username'] . '/';
        if (!is_dir($dirDataset)) {
            mkdir($dirDataset, 0755, true);
        }

        $jumlahAda = count(glob($dirDataset . '*.jpg'));
        if ($jumlahAda >= 10) {
            Response::json(['status' => 'penuh', 'jumlah' => $jumlahAda,
                'pesan' => 'Dataset sudah lengkap (10 foto).']);
            return;
        }

        $dataGambar = base64_decode(preg_replace('/^data:image\/jpeg;base64,/', '', $base64));
        if ($dataGambar === false || strlen($dataGambar) < 1000) {
            Response::json(['status' => 'error', 'pesan' => 'Data gambar rusak.'], 400);
            return;
        }

        $namaFile = 'foto_' . ($jumlahAda + 1) . '.jpg';
        file_put_contents($dirDataset . $namaFile, $dataGambar);

        $jumlahBaru = $jumlahAda + 1;
        Response::json([
            'status' => 'ok',
            'jumlah' => $jumlahBaru,
            'selesai' => $jumlahBaru >= 10,
            'pesan'  => "Foto $jumlahBaru/10 tersimpan.",
        ]);
    }

    public function hapusDataset(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::redirectDenganPesan('dashboard', 'gagal', 'Akses tidak valid.');
            return;
        }
        Auth::cekRole(['guru']);
        $guruId = Auth::idSaatIni() ?? 0;
        $guru   = $this->penggunaModel->cariById($guruId);

        if (!$guru) {
            Response::redirectDenganPesan('dashboard', 'gagal', 'Guru tidak ditemukan.');
            return;
        }

        $dirDataset = BASE_PATH . '/python/dataset/' . $guru['username'] . '/';
        if (is_dir($dirDataset)) {
            $files = array_diff(scandir($dirDataset), ['.', '..']);
            foreach ($files as $file) {
                unlink($dirDataset . $file);
            }
            rmdir($dirDataset);
        }

        Response::redirectDenganPesan(
            'absensi-guru/dataset', 'sukses',
            'Dataset wajah Anda berhasil dihapus. Silakan ambil foto ulang.'
        );
    }

    public function rekap(): void {
        Auth::cekRole(['admin', 'guru', 'kepala_sekolah']);

        $role = Auth::roleSaatIni();
        $guruLoginId = Auth::idSaatIni() ?? 0;
        $filter = [
            'guru_id'         => (int) ($_GET['guru_id'] ?? 0),
            'tanggal_dari'    => $_GET['tanggal_dari'] ?? date('Y-m-d'),
            'tanggal_sampai'  => $_GET['tanggal_sampai'] ?? date('Y-m-d'),
            'status'          => $_GET['status'] ?? '',
        ];

        if ($role === 'guru') {
            $filter['guru_id'] = $guruLoginId;
        }

        $daftarGuru    = $role === 'guru' ? [] : $this->penggunaModel->ambilSemua('guru');
        $dataAbsensi   = $this->absensiGuruModel->ambilDenganFilter($filter);
        $judulHalaman  = 'Rekap Absensi Guru';

        require_once BASE_PATH . '/views/layouts/header.php';
        require_once BASE_PATH . '/views/layouts/sidebar.php';
        require_once BASE_PATH . '/views/absensi_guru/rekap.php';
        require_once BASE_PATH . '/views/layouts/footer.php';
    }

    private function tentukanStatus(array $jadwal, string $jam): string {
        $selisihMenit = (strtotime($jam) - strtotime($jadwal['jam_mulai'])) / 60;
        return $selisihMenit > TOLERANSI_TERLAMBAT ? 'terlambat' : 'hadir';
    }

    private function hitungHaversine(float $lat1, float $lon1, float $lat2, float $lon2): float {
        $radiusBumi = 6371000;
        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $dphi = deg2rad($lat2 - $lat1);
        $dlam = deg2rad($lon2 - $lon1);

        $a = sin($dphi / 2) ** 2 + cos($phi1) * cos($phi2) * sin($dlam / 2) ** 2;
        return $radiusBumi * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function ambilKoordinatPost(string $nama): ?float {
        if (!isset($_POST[$nama]) || $_POST[$nama] === '' || !is_numeric($_POST[$nama])) {
            return null;
        }
        return (float) $_POST[$nama];
    }
}
