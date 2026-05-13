<?php

define('APP_NAME', 'Sistem Absensi SMK Nusantara');

// Auto-detect: php -S (port 8000) vs Apache XAMPP (/absensi-cnn)
define('APP_URL',
    PHP_SAPI === 'cli-server'
        ? 'http://localhost:8000'
        : 'http://localhost/absensi-cnn'
);

define('BASE_PATH', dirname(__DIR__));

// Threshold confidence CNN
define('THRESHOLD_CONFIDENCE', 0.85);
define('THRESHOLD_PERINGATAN', 0.70);

// URL Python CNN service
define('CNN_SERVICE_URL', 'http://127.0.0.1:5000');

// Interpreter Python untuk training dan service.
// Kosongkan agar sistem otomatis memakai virtualenv lokal jika ada.
define('PYTHON_BIN', '');

// Toleransi keterlambatan (menit)
define('TOLERANSI_TERLAMBAT', 15);

// Geofencing GPS — radius maksimal siswa dari koordinat kelas (meter)
define('RADIUS_MAKSIMAL', 50);

// Upload
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB
define('UPLOAD_FOTO_DIR', BASE_PATH . '/public/gambar/foto-siswa/');

date_default_timezone_set('Asia/Makassar'); // WITA
