<?php

class RpaController {

    private UiPathBot $bot;

    public function __construct() {
        Auth::cekRole(['admin']);
        require_once BASE_PATH . '/rpa/ProsesAbsensi.php';
        require_once BASE_PATH . '/rpa/TelegramBot.php';
        require_once BASE_PATH . '/rpa/KirimNotifikasi.php';
        require_once BASE_PATH . '/rpa/GenerateLaporan.php';
        require_once BASE_PATH . '/rpa/UiPathBot.php';

        $this->bot = new UiPathBot(koneksiDB());
    }

    public function index(): void {
        $statistik    = $this->bot->ambilStatistik();
        $logBot       = $this->bot->bacaLog(80);
        $judulHalaman = 'RPA UiPath Bot';

        require_once BASE_PATH . '/views/layouts/header.php';
        require_once BASE_PATH . '/views/layouts/sidebar.php';
        require_once BASE_PATH . '/views/rpa/index.php';
        require_once BASE_PATH . '/views/layouts/footer.php';
    }

    public function jalankan(): void {
        $this->bot->jalankan();
        Response::redirectDenganPesan('rpa', 'sukses', 'UiPath Bot dijalankan manual.');
    }
}
