<?php

class SiswaController {

    private Siswa $siswaModel;
    private Kelas $kelasModel;

    public function __construct() {
        Auth::cekRole(['admin']);
        $this->siswaModel = new Siswa();
        $this->kelasModel = new Kelas();
    }

    public function index(): void {
        $kelasId      = isset($_GET['kelas_id']) ? (int) $_GET['kelas_id'] : 0;
        $daftarSiswa  = $this->siswaModel->ambilSemua($kelasId ?: null);
        $daftarKelas  = $this->kelasModel->ambilSemua();
        $judulHalaman = 'Kelola Siswa';

        require_once BASE_PATH . '/views/layouts/header.php';
        require_once BASE_PATH . '/views/layouts/sidebar.php';
        require_once BASE_PATH . '/views/siswa/index.php';
        require_once BASE_PATH . '/views/layouts/footer.php';
    }

    public function formTambah(): void {
        $daftarKelas  = $this->kelasModel->ambilSemua();
        $judulHalaman = 'Tambah Siswa';
        $siswa        = [];

        require_once BASE_PATH . '/views/layouts/header.php';
        require_once BASE_PATH . '/views/layouts/sidebar.php';
        require_once BASE_PATH . '/views/siswa/form.php';
        require_once BASE_PATH . '/views/layouts/footer.php';
    }

    public function simpan(): void {
        $nama    = trim($_POST['nama'] ?? '');
        $nis     = trim($_POST['nis'] ?? '');
        $kelasId = (int) ($_POST['kelas_id'] ?? 0);

        $validator = new Validator();
        $validator->wajib('nama', $nama, 'Nama')
                  ->wajib('nis', $nis, 'NIS')
                  ->wajib('kelas_id', $kelasId ?: '', 'Kelas')
                  ->minPanjang('nis', $nis, 5, 'NIS')
                  ->maksLong('nis', $nis, 20, 'NIS');

        if (!$validator->valid()) {
            Response::redirectDenganPesan('siswa/tambah', 'gagal',
                implode(' ', $validator->ambilKesalahan()));
            return;
        }

        if ($this->siswaModel->cariByNis($nis)) {
            Response::redirectDenganPesan('siswa/tambah', 'gagal', "NIS $nis sudah terdaftar.");
            return;
        }

        $fotoPath = $this->prosesUploadFoto();

        $this->siswaModel->simpan([
            'nama'     => $nama,
            'nis'      => $nis,
            'kelas_id' => $kelasId,
            'foto'     => $fotoPath,
        ]);

        Response::redirectDenganPesan('siswa', 'sukses', "Siswa $nama berhasil ditambahkan.");
    }

    public function formEdit(): void {
        $id    = (int) ($_GET['id'] ?? 0);
        $siswa = $this->siswaModel->cariById($id);

        if (!$siswa) {
            Response::redirectDenganPesan('siswa', 'gagal', 'Data siswa tidak ditemukan.');
            return;
        }

        $daftarKelas  = $this->kelasModel->ambilSemua();
        $judulHalaman = 'Edit Siswa';

        require_once BASE_PATH . '/views/layouts/header.php';
        require_once BASE_PATH . '/views/layouts/sidebar.php';
        require_once BASE_PATH . '/views/siswa/form.php';
        require_once BASE_PATH . '/views/layouts/footer.php';
    }

    public function update(): void {
        $id      = (int) ($_POST['id'] ?? 0);
        $nama    = trim($_POST['nama'] ?? '');
        $nis     = trim($_POST['nis'] ?? '');
        $kelasId = (int) ($_POST['kelas_id'] ?? 0);
        $aktif   = (int) ($_POST['aktif'] ?? 1);

        $siswaSaatIni = $this->siswaModel->cariById($id);
        if (!$siswaSaatIni) {
            Response::redirectDenganPesan('siswa', 'gagal', 'Data siswa tidak ditemukan.');
            return;
        }

        // Cek NIS duplikat hanya jika NIS berubah
        if ($nis !== $siswaSaatIni['nis'] && $this->siswaModel->cariByNis($nis)) {
            Response::redirectDenganPesan("siswa/edit?id=$id", 'gagal', "NIS $nis sudah digunakan siswa lain.");
            return;
        }

        $fotoPath = $this->prosesUploadFoto();

        $this->siswaModel->update($id, [
            'nama'     => $nama,
            'nis'      => $nis,
            'kelas_id' => $kelasId,
            'aktif'    => $aktif,
            'foto'     => $fotoPath ?: null,
        ]);

        Response::redirectDenganPesan('siswa', 'sukses', "Data $nama berhasil diperbarui.");
    }

    public function hapus(): void {
        $id    = (int) ($_POST['id'] ?? 0);
        $siswa = $this->siswaModel->cariById($id);

        if (!$siswa) {
            Response::redirectDenganPesan('siswa', 'gagal', 'Data siswa tidak ditemukan.');
            return;
        }

        $this->siswaModel->hapus($id);
        Response::redirectDenganPesan('siswa', 'sukses', "Siswa {$siswa['nama']} berhasil dihapus.");
    }

    public function dataset(): void {
        $id    = (int) ($_GET['id'] ?? 0);
        $siswa = $this->siswaModel->cariById($id);

        if (!$siswa) {
            Response::redirectDenganPesan('siswa', 'gagal', 'Data siswa tidak ditemukan.');
            return;
        }

        $dirDataset   = BASE_PATH . '/python/dataset/' . $siswa['nis'] . '/';
        $jumlahFoto   = is_dir($dirDataset) ? count(glob($dirDataset . '*.jpg')) : 0;
        $judulHalaman = 'Dataset Wajah — ' . $siswa['nama'];

        require_once BASE_PATH . '/views/layouts/header.php';
        require_once BASE_PATH . '/views/layouts/sidebar.php';
        require_once BASE_PATH . '/views/siswa/dataset.php';
        require_once BASE_PATH . '/views/layouts/footer.php';
    }

    // Terima foto base64 dari JS, simpan ke python/dataset/[nis]/
    public function simpanDataset(): void {
        header('Content-Type: application/json');

        $id    = (int) ($_POST['siswa_id'] ?? 0);
        $siswa = $this->siswaModel->cariById($id);

        if (!$siswa) {
            Response::json(['status' => 'error', 'pesan' => 'Siswa tidak ditemukan.'], 404);
            return;
        }

        $base64 = $_POST['gambar'] ?? '';
        if (!preg_match('/^data:image\/jpeg;base64,/', $base64)) {
            Response::json(['status' => 'error', 'pesan' => 'Format gambar tidak valid.'], 400);
            return;
        }

        $dirDataset = BASE_PATH . '/python/dataset/' . $siswa['nis'] . '/';
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

    // Hapus semua foto dataset satu siswa
    public function hapusDataset(): void {
        $id    = (int) ($_POST['siswa_id'] ?? 0);
        $siswa = $this->siswaModel->cariById($id);

        if (!$siswa) {
            Response::redirectDenganPesan('siswa', 'gagal', 'Siswa tidak ditemukan.');
            return;
        }

        $dirDataset = BASE_PATH . '/python/dataset/' . $siswa['nis'] . '/';
        if (is_dir($dirDataset)) {
            array_map('unlink', glob($dirDataset . '*.jpg'));
        }

        Response::redirectDenganPesan(
            'siswa/dataset?id=' . $id, 'sukses',
            'Dataset wajah ' . $siswa['nama'] . ' berhasil dihapus.'
        );
    }

    private function prosesUploadFoto(): ?string {
        if (empty($_FILES['foto']['tmp_name'])) return null;

        $file     = $_FILES['foto'];
        $ekstensi = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $izin     = ['jpg', 'jpeg', 'png'];

        if (!in_array($ekstensi, $izin, true)) return null;
        if ($file['size'] > MAX_UPLOAD_SIZE) return null;

        // Validasi magic bytes untuk memastikan file benar-benar gambar
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, ['image/jpeg', 'image/png'], true)) return null;

        $namaFile = uniqid('siswa_', true) . '.' . $ekstensi;
        $tujuan   = UPLOAD_FOTO_DIR . $namaFile;

        if (move_uploaded_file($file['tmp_name'], $tujuan)) {
            return 'gambar/foto-siswa/' . $namaFile;
        }
        return null;
    }
}
