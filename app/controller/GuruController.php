<?php

class GuruController {

    private Pengguna $penggunaModel;

    public function __construct() {
        Auth::cekRole(['admin']);
        $this->penggunaModel = new Pengguna();
    }

    public function index(): void {
        $daftarGuru   = $this->penggunaModel->ambilGuruDenganStatistik();
        $judulHalaman = 'Kelola Guru';

        require_once BASE_PATH . '/views/layouts/header.php';
        require_once BASE_PATH . '/views/layouts/sidebar.php';
        require_once BASE_PATH . '/views/guru/index.php';
        require_once BASE_PATH . '/views/layouts/footer.php';
    }

    public function simpan(): void {
        $id       = (int) ($_POST['id'] ?? 0);
        $nama     = trim($_POST['nama'] ?? '');
        $username = strtolower(trim($_POST['username'] ?? ''));
        $password = trim($_POST['password'] ?? '');

        $validator = new Validator();
        $validator->wajib('nama', $nama, 'Nama')
                  ->wajib('username', $username, 'Username')
                  ->minPanjang('username', $username, 3, 'Username')
                  ->maksLong('username', $username, 50, 'Username')
                  ->hanyaAngkaHuruf('username', $username, 'Username');

        if ($id <= 0 && $password === '') {
            Response::redirectDenganPesan('guru', 'gagal', 'Password wajib diisi saat menambah guru.');
            return;
        }

        if ($password !== '' && mb_strlen($password) < 6) {
            Response::redirectDenganPesan('guru', 'gagal', 'Password minimal 6 karakter.');
            return;
        }

        if (!$validator->valid()) {
            Response::redirectDenganPesan('guru', 'gagal',
                implode(' ', $validator->ambilKesalahan()));
            return;
        }

        if ($id > 0) {
            $guruSaatIni = $this->penggunaModel->cariById($id);
            if (!$guruSaatIni || $guruSaatIni['role'] !== 'guru') {
                Response::redirectDenganPesan('guru', 'gagal', 'Data guru tidak ditemukan.');
                return;
            }

            if ($this->penggunaModel->cariByUsernameSelainId($username, $id)) {
                Response::redirectDenganPesan('guru', 'gagal', "Username $username sudah digunakan.");
                return;
            }

            if ($username !== $guruSaatIni['username'] && is_dir($this->dirDataset($username))) {
                Response::redirectDenganPesan('guru', 'gagal', "Folder dataset untuk username $username sudah ada.");
                return;
            }

            $this->penggunaModel->update($id, [
                'nama'     => $nama,
                'username' => $username,
                'password' => $password,
                'role'     => 'guru',
            ]);
            $this->gantiNamaDirDataset($guruSaatIni['username'], $username);

            Response::redirectDenganPesan('guru', 'sukses', "Data guru $nama berhasil diperbarui.");
            return;
        }

        if ($this->penggunaModel->cariByUsername($username)) {
            Response::redirectDenganPesan('guru', 'gagal', "Username $username sudah digunakan.");
            return;
        }

        $this->penggunaModel->simpan([
            'nama'     => $nama,
            'username' => $username,
            'password' => $password,
            'role'     => 'guru',
        ]);

        Response::redirectDenganPesan('guru', 'sukses', "Guru $nama berhasil ditambahkan.");
    }

    public function hapus(): void {
        $id   = (int) ($_POST['id'] ?? 0);
        $guru = $this->penggunaModel->cariById($id);

        if (!$guru || $guru['role'] !== 'guru') {
            Response::redirectDenganPesan('guru', 'gagal', 'Data guru tidak ditemukan.');
            return;
        }

        $this->penggunaModel->hapusGuruBesertaRelasi($id);
        $this->hapusDirDataset($guru['username']);

        Response::redirectDenganPesan('guru', 'sukses', "Guru {$guru['nama']} berhasil dihapus.");
    }

    private function dirDataset(string $username): string {
        return BASE_PATH . '/python/dataset/' . $username . '/';
    }

    private function gantiNamaDirDataset(string $usernameLama, string $usernameBaru): void {
        if ($usernameLama === $usernameBaru) return;

        $dirLama = $this->dirDataset($usernameLama);
        $dirBaru = $this->dirDataset($usernameBaru);
        if (is_dir($dirLama) && !is_dir($dirBaru)) {
            rename($dirLama, $dirBaru);
        }
    }

    private function hapusDirDataset(string $username): void {
        $dir = $this->dirDataset($username);
        if (!is_dir($dir)) return;

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . $file;
            if (is_file($path)) {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
