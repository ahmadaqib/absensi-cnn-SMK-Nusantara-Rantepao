<?php

class Response {

    public static function json(array $data, int $kodeStatus = 200): void {
        http_response_code($kodeStatus);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function redirect(string $path): void {
        header('Location: ' . APP_URL . '/' . ltrim($path, '/'));
        exit;
    }

    public static function redirectDenganPesan(string $path, string $tipe, string $pesan): void {
        Auth::mulaiSesi();
        $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
        self::redirect($path);
    }

    // Ambil dan hapus pesan flash dari sesi
    public static function ambilFlash(): ?array {
        Auth::mulaiSesi();
        if (!empty($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
}
