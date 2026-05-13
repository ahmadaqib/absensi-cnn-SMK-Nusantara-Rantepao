<?php

class AuthController {

    private Pengguna $penggunaModel;

    public function __construct() {
        $this->penggunaModel = new Pengguna();
    }

    public function indexLogin(): void {
        // Jika sudah login, langsung ke dashboard
        Auth::mulaiSesi();
        if (!empty($_SESSION['pengguna_id'])) {
            Response::redirect('dashboard');
        }

        $judulHalaman = 'Masuk';
        $inputUsername = '';
        $kesalahan = '';
        require_once BASE_PATH . '/views/auth/login.php';
    }

    public function prosesLogin(): void {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $validator = new Validator();
        $validator->wajib('username', $username, 'Username')
                  ->wajib('password', $password, 'Password');

        if (!$validator->valid()) {
            $judulHalaman = 'Masuk';
            $inputUsername = htmlspecialchars($username);
            $kesalahan = 'Username dan password wajib diisi.';
            require_once BASE_PATH . '/views/auth/login.php';
            return;
        }

        $pengguna = $this->penggunaModel->cariByUsername($username);

        if (!$pengguna || !password_verify($password, $pengguna['password'])) {
            $judulHalaman = 'Masuk';
            $inputUsername = htmlspecialchars($username);
            $kesalahan = 'Username atau password salah.';
            require_once BASE_PATH . '/views/auth/login.php';
            return;
        }

        Auth::simpanSesi($pengguna);
        Response::redirect('dashboard');
    }

    public function logout(): void {
        Auth::hapusSesi();
        Response::redirect('login');
    }
}
