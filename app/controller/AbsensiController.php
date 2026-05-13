<?php

class AbsensiController {

    private Absensi    $absensiModel;
    private Siswa      $siswaModel;
    private Jadwal     $jadwalModel;
    private Kelas      $kelasModel;
    private CNNService $cnn;

    public function __construct() {
        Auth::cekLogin();
        $this->absensiModel = new Absensi();
        $this->siswaModel   = new Siswa();
        $this->jadwalModel  = new Jadwal();
        $this->kelasModel   = new Kelas();
        $this->cnn          = new CNNService();
    }

    // Halaman kamera absensi
    public function kamera(): void {
        $daftarKelas   = $this->kelasModel->ambilSemua();
        $kelasId       = (int) ($_GET['kelas_id'] ?? 0);
        $jadwalId      = (int) ($_GET['jadwal_id'] ?? 0);

        if (!$kelasId) {
            foreach ($daftarKelas as $kelas) {
                $jadwalUntukKelas = $this->jadwalModel->ambilHariIni((int) $kelas['id']);
                if (!empty($jadwalUntukKelas)) {
                    $kelasId       = (int) $kelas['id'];
                    $jadwalHariIni = $jadwalUntukKelas;
                    break;
                }
            }
            $kelasId = $kelasId ?: (int) ($daftarKelas[0]['id'] ?? 0);
        }

        $jadwalHariIni = $jadwalHariIni ?? $this->jadwalModel->ambilHariIni($kelasId);
        $jadwalId      = $jadwalId ?: (int) ($jadwalHariIni[0]['id'] ?? 0);
        $statusCnn     = $this->cnn->cekStatus();

        // Info GPS kelas yang dipilih (untuk ditampilkan di UI)
        $koordinatKelas = $kelasId ? $this->kelasModel->ambilKoordinat($kelasId) : null;
        $kelasAdaGps    = $koordinatKelas && !empty($koordinatKelas['latitude']);

        $judulHalaman  = 'Absensi Kamera';

        require_once BASE_PATH . '/views/layouts/header.php';
        require_once BASE_PATH . '/views/layouts/sidebar.php';
        require_once BASE_PATH . '/views/absensi/kamera.php';
        require_once BASE_PATH . '/views/layouts/footer.php';
    }

    // AJAX endpoint — dipanggil JS setiap 2 detik
    public function proses(): void {
        $gambar   = $_POST['gambar']   ?? '';
        $jadwalId = (int) ($_POST['jadwal_id'] ?? 0);
        $latSiswa = $this->ambilKoordinatPost('latitude');
        $lonSiswa = $this->ambilKoordinatPost('longitude');

        if (!$gambar || !$jadwalId) {
            Response::json(['status' => 'error', 'pesan' => 'Data tidak lengkap.'], 400);
            return;
        }

        $jadwal = $this->jadwalModel->cariById($jadwalId);
        if (!$jadwal) {
            Response::json(['status' => 'error', 'pesan' => 'Jadwal tidak ditemukan.'], 404);
            return;
        }

        // ── CNN service ──
        $hasilCnn = $this->cnn->kenaliWajah($gambar);

        if ($hasilCnn === null) {
            $detail = $this->cnn->getLastError();
            Response::json([
                'status' => 'error',
                'pesan'  => 'CNN service aktif, tetapi endpoint pengenalan wajah tidak merespons'
                    . ($detail ? ": $detail" : '. Coba ulangi atau restart Python service.'),
            ]);
            return;
        }

        if ($hasilCnn['status'] !== 'berhasil') {
            Response::json($hasilCnn);
            return;
        }

        // ── Cari siswa ──
        $nis   = $hasilCnn['nis'] ?? '';
        $siswa = $this->siswaModel->cariByNis($nis);

        if (!$siswa) {
            Response::json(['status' => 'error', 'pesan' => "NIS $nis tidak ditemukan di database."]);
            return;
        }

        if ((int) $siswa['kelas_id'] !== (int) $jadwal['kelas_id']) {
            $jadwalRekomendasi = $this->jadwalModel->ambilHariIni((int) $siswa['kelas_id']);
            $jadwalRekomendasiId = (int) ($jadwalRekomendasi[0]['id'] ?? 0);
            Response::json([
                'status'          => 'salah_kelas',
                'nama_siswa'      => $siswa['nama'],
                'nis'             => $nis,
                'confidence'      => $hasilCnn['confidence'],
                'kelas_id'        => (int) $siswa['kelas_id'],
                'nama_kelas'      => $siswa['nama_kelas'] ?? 'kelas siswa',
                'jadwal_id'       => $jadwalRekomendasiId,
                'redirect_url'    => APP_URL . '/absensi?kelas_id=' . (int) $siswa['kelas_id'] . ($jadwalRekomendasiId ? '&jadwal_id=' . $jadwalRekomendasiId : ''),
                'pesan'           => "{$siswa['nama']} terdaftar di kelas " . ($siswa['nama_kelas'] ?? '-') . '. Pilih kelas tersebut untuk absensi.',
            ]);
            return;
        }

        // ── Validasi GPS di server setelah kelas siswa benar ──
        $koordinat = $this->kelasModel->ambilKoordinat((int) $jadwal['kelas_id']);
        if ($koordinat && !empty($koordinat['latitude'])) {
            if ($latSiswa === null || $lonSiswa === null) {
                Response::json([
                    'status' => 'gps_belum_siap',
                    'pesan'  => 'Lokasi belum diterima server. Tunggu beberapa detik, pastikan ikon lokasi browser aktif, lalu coba lagi.',
                ]);
                return;
            }

            $jarak = $this->hitungHaversine(
                $latSiswa, $lonSiswa,
                (float) $koordinat['latitude'],
                (float) $koordinat['longitude']
            );
            $radiusMaks = $koordinat['radius'] ?? RADIUS_MAKSIMAL;

            if ($jarak > $radiusMaks) {
                Response::json([
                    'status' => 'error_gps',
                    'pesan'  => sprintf('Di luar area kelas (%.0f m dari kelas, maks %d m).', $jarak, $radiusMaks),
                    'jarak'  => round($jarak),
                ]);
                return;
            }
        }
        $jarak = $jarak ?? null;

        // ── Cek duplikasi (final + antrian) ──
        $tanggal = date('Y-m-d');
        if ($this->absensiModel->sudahAbsen($siswa['id'], $jadwalId, $tanggal)
            || $this->absensiModel->sudahDiAntrian($siswa['id'], $jadwalId)) {
            Response::json([
                'status'     => 'duplikat',
                'nama_siswa' => $siswa['nama'],
                'nis'        => $nis,
                'confidence' => $hasilCnn['confidence'],
                'pesan'      => $siswa['nama'] . ' sudah absen hari ini.',
            ]);
            return;
        }

        $jam = date('H:i:s');
        $statusAbsensi = $this->tentukanStatus($jadwalId, $jam);

        // Simpan final sekarang agar rekap dan laporan langsung terisi.
        $tersimpan = $this->absensiModel->simpan([
            'siswa_id'           => $siswa['id'],
            'jadwal_id'          => $jadwalId,
            'tanggal'            => $tanggal,
            'jam'                => $jam,
            'status'             => $statusAbsensi,
            'confidence'         => $hasilCnn['confidence'],
            'latitude_absensi'   => $latSiswa,
            'longitude_absensi'  => $lonSiswa,
            'jarak_dari_kelas'   => isset($jarak) ? round($jarak, 2) : null,
        ]);

        if (!$tersimpan) {
            Response::json(['status' => 'error', 'pesan' => 'Absensi gagal disimpan ke rekap.']);
            return;
        }

        // Simpan jejak RPA sebagai DONE supaya bot/notifikasi tetap punya audit trail.
        try {
            $this->absensiModel->simpanAntrian([
                'siswa_id'        => $siswa['id'],
                'jadwal_id'       => $jadwalId,
                'confidence'      => $hasilCnn['confidence'],
                'latitude'        => $latSiswa,
                'longitude'       => $lonSiswa,
                'jarak_dari_kelas'=> isset($jarak) ? round($jarak, 2) : null,
                'status'          => 'DONE',
            ]);
        } catch (Throwable $e) {
            // Rekap/laporan sudah aman karena tabel final berhasil disimpan.
        }

        Response::json([
            'status'         => 'berhasil',
            'nama_siswa'     => $siswa['nama'],
            'nis'            => $nis,
            'jam'            => substr($jam, 0, 5),
            'status_absensi' => $statusAbsensi,
            'confidence'     => $hasilCnn['confidence'],
            'jarak'          => isset($jarak) ? round($jarak) : null,
            'pesan'          => 'Absensi ' . $siswa['nama'] . ' berhasil dicatat.',
        ]);
    }

    // Halaman rekap absensi
    public function rekap(): void {
        Auth::cekRole(['admin', 'guru', 'kepala_sekolah']);
        $daftarKelas  = $this->kelasModel->ambilSemua();
        $judulHalaman = 'Rekap Absensi';

        $filter = [
            'kelas_id'       => (int) ($_GET['kelas_id'] ?? 0),
            'tanggal_dari'   => $_GET['tanggal_dari']   ?? date('Y-m-d'),
            'tanggal_sampai' => $_GET['tanggal_sampai'] ?? date('Y-m-d'),
            'status'         => $_GET['status'] ?? '',
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

    // Haversine formula — hitung jarak dua titik GPS dalam meter
    private function hitungHaversine(float $lat1, float $lon1, float $lat2, float $lon2): float {
        $R    = 6371000;
        $phi1 = deg2rad($lat1);
        $phi2 = deg2rad($lat2);
        $dphi = deg2rad($lat2 - $lat1);
        $dlam = deg2rad($lon2 - $lon1);

        $a = sin($dphi / 2) ** 2 + cos($phi1) * cos($phi2) * sin($dlam / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function ambilKoordinatPost(string $nama): ?float {
        if (!isset($_POST[$nama]) || $_POST[$nama] === '' || !is_numeric($_POST[$nama])) {
            return null;
        }
        return (float) $_POST[$nama];
    }
}
