<?php

class PanduanController {

    public function __construct() {
        Auth::cekRole(['admin']);
    }

    public function index(): void {
        $judulHalaman = 'Panduan';

        require_once BASE_PATH . '/views/layouts/header.php';
        require_once BASE_PATH . '/views/layouts/sidebar.php';
        require_once BASE_PATH . '/views/panduan/index.php';
        require_once BASE_PATH . '/views/layouts/footer.php';
    }
}
