<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'sistem_absensi');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function koneksiDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dsn  = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $opsi = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $opsi);
    } catch (PDOException $e) {
        $pesanError = $e->getMessage();
        $dbBelumAda = str_contains($pesanError, '1049') || str_contains($pesanError, 'Unknown database');
        $mysqlMati  = str_contains($pesanError, '2002') || str_contains($pesanError, 'Connection refused');

        http_response_code(503);
        $judulHalaman = 'Setup Diperlukan';
        require_once dirname(__DIR__) . '/views/error_db.php';
        exit;
    }

    return $pdo;
}
