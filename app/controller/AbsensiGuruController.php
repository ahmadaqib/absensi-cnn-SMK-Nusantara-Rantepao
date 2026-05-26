<?php

class AbsensiGuruController {

    private AbsensiGuru $absensiGuruModel;
    private Jadwal      $jadwalModel;
    private Kelas       $kelasModel;
    private Pengguna    $penggunaModel;

    public function __construct() {
        Auth::cekLogin();
        $this->absensiGuruModel = new AbsensiGuru();
        $this->jadwalModel      = new Jadwal();
        $this->kelasModel       = new Kelas();
        $this->penggunaModel    = new Pengguna();
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

        if (!$guruId || !$jadwalId) {
            Response::redirectDenganPesan('absensi-guru', 'gagal', 'Jadwal absensi guru tidak valid.');
        }

        $jadwal = $this->jadwalModel->cariById($jadwalId);
        if (!$jadwal || (int) $jadwal['guru_id'] !== $guruId) {
            Response::redirectDenganPesan('absensi-guru', 'gagal', 'Jadwal tidak ditemukan untuk akun guru ini.');
        }

        $tanggal = date('Y-m-d');
        if ($this->absensiGuruModel->sudahAbsen($guruId, $jadwalId, $tanggal)) {
            Response::redirectDenganPesan('absensi-guru', 'gagal', 'Kehadiran untuk jadwal ini sudah dicatat hari ini.');
        }

        $koordinat = $this->kelasModel->ambilKoordinat((int) $jadwal['kelas_id']);
        if ($koordinat && !empty($koordinat['latitude'])) {
            if ($latGuru === null || $lonGuru === null) {
                Response::redirectDenganPesan('absensi-guru', 'gagal', 'Lokasi GPS belum terbaca. Izinkan lokasi browser lalu coba lagi.');
            }

            $jarak = $this->hitungHaversine(
                $latGuru,
                $lonGuru,
                (float) $koordinat['latitude'],
                (float) $koordinat['longitude']
            );
            $radiusMaks = (int) ($koordinat['radius'] ?? RADIUS_MAKSIMAL);

            if ($jarak > $radiusMaks) {
                Response::redirectDenganPesan(
                    'absensi-guru',
                    'gagal',
                    sprintf('Di luar area kelas (%.0f m dari kelas, maks %d m).', $jarak, $radiusMaks)
                );
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
            Response::redirectDenganPesan('absensi-guru', 'gagal', 'Kehadiran guru gagal disimpan.');
        }

        Response::redirectDenganPesan('absensi-guru', 'sukses', 'Kehadiran guru berhasil dicatat.');
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
