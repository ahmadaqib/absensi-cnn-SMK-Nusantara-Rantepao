<?php

class KelasController {

    private Kelas $kelasModel;

    public function __construct() {
        Auth::cekRole(['admin']);
        $this->kelasModel = new Kelas();
    }

    public function index(): void {
        $daftarKelas  = $this->kelasModel->ambilSemua();
        $judulHalaman = 'Kelola Kelas';

        require_once BASE_PATH . '/views/layouts/header.php';
        require_once BASE_PATH . '/views/layouts/sidebar.php';
        require_once BASE_PATH . '/views/kelas/index.php';
        require_once BASE_PATH . '/views/layouts/footer.php';
    }

    public function simpan(): void {
        $id    = (int) ($_POST['id'] ?? 0);
        $nama  = trim($_POST['nama'] ?? '');
        $tahun = trim($_POST['tahun'] ?? '');

        $validator = new Validator();
        $validator->wajib('nama', $nama, 'Nama kelas')
                  ->wajib('tahun', $tahun, 'Tahun ajaran');

        if (!$validator->valid()) {
            Response::redirectDenganPesan('kelas', 'gagal',
                implode(' ', $validator->ambilKesalahan()));
            return;
        }

        if ($id > 0) {
            $this->kelasModel->update($id, ['nama' => $nama, 'tahun' => $tahun]);
            Response::redirectDenganPesan('kelas', 'sukses', "Kelas $nama berhasil diperbarui.");
        } else {
            $this->kelasModel->simpan(['nama' => $nama, 'tahun' => $tahun]);
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

        // Cegah hapus kelas yang masih memiliki siswa
        if ($this->kelasModel->jumlahSiswa($id) > 0) {
            Response::redirectDenganPesan('kelas', 'gagal',
                "Kelas {$kelas['nama']} masih memiliki siswa aktif. Pindahkan siswa terlebih dahulu.");
            return;
        }

        $this->kelasModel->hapus($id);
        Response::redirectDenganPesan('kelas', 'sukses', "Kelas {$kelas['nama']} berhasil dihapus.");
    }
}
