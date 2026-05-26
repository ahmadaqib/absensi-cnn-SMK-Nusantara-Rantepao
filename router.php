<?php
/**
 * Router untuk PHP built-in server (php -S localhost:8000 router.php).
 * File statis (CSS, JS, gambar) di-serve langsung.
 * Semua request lain diteruskan ke index.php.
 */

$urlPath  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$filePath = __DIR__ . $urlPath;

// Serve file statis langsung (return false = PHP handle sendiri)
if (is_file($filePath) && !str_ends_with($filePath, '.php')) {
    return false;
}

require_once __DIR__ . '/index.php';
