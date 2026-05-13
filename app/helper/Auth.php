<?php

class Auth {

    public static function mulaiSesi(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function cekLogin(): void {
        self::mulaiSesi();
        if (empty($_SESSION['pengguna_id'])) {
            header('Location: ' . APP_URL . '/login');
            exit;
        }
    }

    // Hanya izinkan role tertentu, redirect jika tidak sesuai
    public static function cekRole(array $roleDiizinkan): void {
        self::cekLogin();
        if (!in_array($_SESSION['role'], $roleDiizinkan, true)) {
            header('Location: ' . APP_URL . '/dashboard');
            exit;
        }
    }

    public static function simpanSesi(array $pengguna): void {
        self::mulaiSesi();
        session_regenerate_id(true);
        $_SESSION['pengguna_id'] = $pengguna['id'];
        $_SESSION['nama']        = $pengguna['nama'];
        $_SESSION['username']    = $pengguna['username'];
        $_SESSION['role']        = $pengguna['role'];
    }

    public static function hapusSesi(): void {
        self::mulaiSesi();
        session_unset();
        session_destroy();
    }

    public static function roleSaatIni(): string {
        return $_SESSION['role'] ?? '';
    }

    public static function idSaatIni(): ?int {
        return isset($_SESSION['pengguna_id']) ? (int) $_SESSION['pengguna_id'] : null;
    }

    public static function namaSaatIni(): string {
        return $_SESSION['nama'] ?? '';
    }
}
