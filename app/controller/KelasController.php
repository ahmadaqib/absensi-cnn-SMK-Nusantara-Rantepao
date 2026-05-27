<?php

class KelasController {

    private Kelas $kelasModel;
    private Pengaturan $pengaturanModel;

    public function __construct() {
        Auth::cekRole(['admin']);
        $this->kelasModel      = new Kelas();
        $this->pengaturanModel = new Pengaturan();
    }

    public function index(): void {
        $daftarKelas       = $this->kelasModel->ambilSemua();
        $koordinatSekolah  = $this->pengaturanModel->koordinatSekolah();
        $sekolahAdaGps     = $koordinatSekolah['latitude'] !== '' && $koordinatSekolah['longitude'] !== '';
        $judulHalaman      = 'Kelola Kelas';

        require_once BASE_PATH . '/views/layouts/header.php';
        require_once BASE_PATH . '/views/layouts/sidebar.php';
        require_once BASE_PATH . '/views/kelas/index.php';
        require_once BASE_PATH . '/views/layouts/footer.php';
    }

    public function simpan(): void {
        $id        = (int) ($_POST['id'] ?? 0);
        $nama      = trim($_POST['nama'] ?? '');
        $tahun     = trim($_POST['tahun'] ?? '');
        $sumber    = trim($_POST['sumber_koordinat'] ?? 'sekolah');
        $latitude  = trim($_POST['latitude'] ?? '');
        $longitude = trim($_POST['longitude'] ?? '');
        $radius    = trim($_POST['radius'] ?? '50');
        $radius    = $radius === '' ? '50' : $radius;

        $validator = new Validator();
        $validator->wajib('nama', $nama, 'Nama kelas')
                  ->wajib('tahun', $tahun, 'Tahun ajaran');

        if (!$validator->valid()) {
            Response::redirectDenganPesan('kelas', 'gagal',
                implode(' ', $validator->ambilKesalahan()));
            return;
        }

        if (!in_array($sumber, ['sekolah', 'kelas'], true)) {
            Response::redirectDenganPesan('kelas', 'gagal', 'Pilihan sumber koordinat tidak valid.');
            return;
        }

        if ($sumber === 'kelas' && ($latitude === '' || $longitude === '')) {
            Response::redirectDenganPesan('kelas', 'gagal', 'Koordinat kelas wajib diisi jika memilih koordinat sendiri.');
            return;
        }

        if ($sumber === 'sekolah') {
            $latitude = '';
            $longitude = '';
            $radius = '';
        }

        if (($latitude !== '' && ((float) $latitude < -90 || (float) $latitude > 90))
            || ($longitude !== '' && ((float) $longitude < -180 || (float) $longitude > 180))) {
            Response::redirectDenganPesan('kelas', 'gagal', 'Koordinat latitude/longitude tidak valid.');
            return;
        }

        if (($latitude === '') !== ($longitude === '')) {
            Response::redirectDenganPesan('kelas', 'gagal', 'Latitude dan longitude harus diisi berpasangan.');
            return;
        }

        if ($sumber === 'kelas' && ((int) $radius < 10 || (int) $radius > 500)) {
            Response::redirectDenganPesan('kelas', 'gagal', 'Radius harus di antara 10 sampai 500 meter.');
            return;
        }

        $payload = [
            'nama'      => $nama,
            'tahun'     => $tahun,
            'sumber_koordinat' => $sumber,
            'latitude'  => $latitude,
            'longitude' => $longitude,
            'radius'    => $sumber === 'kelas' ? ($radius ?: 50) : '',
        ];

        if ($id > 0) {
            $this->kelasModel->update($id, $payload);
            Response::redirectDenganPesan('kelas', 'sukses', "Kelas $nama berhasil diperbarui.");
        } else {
            $this->kelasModel->simpan($payload);
            Response::redirectDenganPesan('kelas', 'sukses', "Kelas $nama berhasil ditambahkan.");
        }
    }

    public function hapus(): void {
        $id    = (int) ($_POST['id'] ?? 0);
        $kelas = $this->kelasModel->cariById($id);

        if (!$kelas) {
            Response::redirectDenganPesan('kelas', 'gagal', 'Data kelas tidak ditemukan.');
            return;
        }

        if ($this->kelasModel->jumlahSiswa($id) > 0) {
            Response::redirectDenganPesan('kelas', 'gagal',
                "Kelas {$kelas['nama']} masih memiliki siswa aktif. Pindahkan siswa terlebih dahulu.");
            return;
        }

        $this->kelasModel->hapus($id);
        Response::redirectDenganPesan('kelas', 'sukses', "Kelas {$kelas['nama']} berhasil dihapus.");
    }
}
