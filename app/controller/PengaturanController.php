<?php

class PengaturanController {

    private Pengaturan $pengaturanModel;

    public function __construct() {
        Auth::cekRole(['admin']);
        $this->pengaturanModel = new Pengaturan();
    }

    public function index(): void {
        $koordinatSekolah = $this->pengaturanModel->koordinatSekolah();
        $judulHalaman     = 'Pengaturan';

        require_once BASE_PATH . '/views/layouts/header.php';
        require_once BASE_PATH . '/views/layouts/sidebar.php';
        require_once BASE_PATH . '/views/pengaturan/index.php';
        require_once BASE_PATH . '/views/layouts/footer.php';
    }

    public function simpanSekolah(): void {
        $latitude  = trim($_POST['latitude'] ?? '');
        $longitude = trim($_POST['longitude'] ?? '');
        $radius    = trim($_POST['radius'] ?? (string) RADIUS_MAKSIMAL);

        if ($latitude === '' || $longitude === '') {
            Response::redirectDenganPesan('pengaturan', 'gagal', 'Latitude dan longitude sekolah wajib diisi.');
            return;
        }

        if (!is_numeric($latitude) || !is_numeric($longitude)
            || (float) $latitude < -90 || (float) $latitude > 90
            || (float) $longitude < -180 || (float) $longitude > 180) {
            Response::redirectDenganPesan('pengaturan', 'gagal', 'Koordinat sekolah tidak valid.');
            return;
        }

        if (!is_numeric($radius) || (int) $radius < 10 || (int) $radius > 1000) {
            Response::redirectDenganPesan('pengaturan', 'gagal', 'Radius sekolah harus di antara 10 sampai 1000 meter.');
            return;
        }

        $this->pengaturanModel->simpanKoordinatSekolah(
            number_format((float) $latitude, 8, '.', ''),
            number_format((float) $longitude, 8, '.', ''),
            (string) (int) $radius
        );

        Response::redirectDenganPesan('pengaturan', 'sukses', 'Koordinat sekolah berhasil disimpan.');
    }
}
