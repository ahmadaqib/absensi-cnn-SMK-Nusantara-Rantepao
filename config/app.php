<?php

define('APP_NAME', 'Sistem Absensi SMK Nusantara');

// Auto-detect: php -S (port 8000) vs production VPS / Apache
define('APP_URL',
    PHP_SAPI === 'cli-server'
        ? 'http://localhost:8001'
        : (isset($_SERVER['HTTP_HOST']) ? (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] : 'https://risna-skripsi.site')
);

define('BASE_PATH', dirname(__DIR__));

// Threshold confidence CNN
define('THRESHOLD_CONFIDENCE', 0.85);
define('THRESHOLD_PERINGATAN', 0.70);

// URL Python CNN service
define('CNN_SERVICE_URL', 'http://127.0.0.1:5001');

// Interpreter Python untuk training dan service.
// Kosongkan agar sistem otomatis memakai virtualenv lokal jika ada.
define('PYTHON_BIN', '');

// Toleransi keterlambatan (menit)
define('TOLERANSI_TERLAMBAT', 15);

// Geofencing GPS — radius maksimal siswa dari koordinat kelas (meter)
define('RADIUS_MAKSIMAL', 50);

// Telegram Bot untuk notifikasi RPA.
// Isi lewat environment variable TELEGRAM_BOT_TOKEN dan TELEGRAM_CHAT_ID,
// atau ganti string kosong di bawah saat instalasi lokal.
define('TELEGRAM_BOT_TOKEN', getenv('TELEGRAM_BOT_TOKEN') ?: '');
define('TELEGRAM_CHAT_ID', getenv('TELEGRAM_CHAT_ID') ?: '');

// Upload
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB
define('UPLOAD_FOTO_DIR', BASE_PATH . '/public/gambar/foto-siswa/');

date_default_timezone_set('Asia/Makassar'); // WITA
