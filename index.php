<?php

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';

// Autoload helper dan model
foreach (['Auth', 'Response', 'Validator', 'XlsxWriter'] as $helper) {
    require_once __DIR__ . "/app/helper/$helper.php";
}
foreach (['Pengguna', 'Siswa', 'Kelas', 'Jadwal', 'Absensi', 'AbsensiGuru', 'Notifikasi'] as $model) {
    $file = __DIR__ . "/app/model/$model.php";
    if (file_exists($file)) require_once $file;
}

$cnnServiceFile = __DIR__ . '/app/service/CNNService.php';
if (file_exists($cnnServiceFile)) require_once $cnnServiceFile;

Auth::mulaiSesi();

// Routing sederhana berbasis path
$uri     = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$appPath = parse_url(APP_URL, PHP_URL_PATH) ?? '';   // null saat tidak ada sub-path (php -S)
$uri     = '/' . trim(str_replace($appPath, '', $uri), '/');

$method = $_SERVER['REQUEST_METHOD'];

// Daftar rute: [method, pola, controller, aksi]
$rute = [
    ['GET',  '/',                   'AuthController',      'indexLogin'],
    ['GET',  '/login',              'AuthController',      'indexLogin'],
    ['POST', '/login',              'AuthController',      'prosesLogin'],
    ['GET',  '/logout',             'AuthController',      'logout'],

    ['GET',  '/dashboard',          'DashboardController', 'index'],
    ['GET',  '/panduan',            'PanduanController',   'index'],

    ['GET',  '/siswa',              'SiswaController',     'index'],
    ['GET',  '/siswa/tambah',       'SiswaController',     'formTambah'],
    ['POST', '/siswa/tambah',       'SiswaController',     'simpan'],
    ['GET',  '/siswa/edit',              'SiswaController',     'formEdit'],
    ['POST', '/siswa/edit',              'SiswaController',     'update'],
    ['POST', '/siswa/hapus',             'SiswaController',     'hapus'],
    ['GET',  '/siswa/dataset',           'SiswaController',     'dataset'],
    ['POST', '/siswa/dataset/simpan',    'SiswaController',     'simpanDataset'],
    ['POST', '/siswa/dataset/hapus',     'SiswaController',     'hapusDataset'],

    ['GET',  '/training',                'TrainingController',  'index'],
    ['POST', '/training/mulai',          'TrainingController',  'mulai'],
    ['GET',  '/training/status',         'TrainingController',  'status'],

    ['GET',  '/kelas',              'KelasController',     'index'],
    ['POST', '/kelas/simpan',       'KelasController',     'simpan'],
    ['POST', '/kelas/hapus',        'KelasController',     'hapus'],

    ['GET',  '/jadwal',             'JadwalController',    'index'],
    ['POST', '/jadwal/simpan',      'JadwalController',    'simpan'],
    ['POST', '/jadwal/hapus',       'JadwalController',    'hapus'],

    ['GET',  '/absensi',            'AbsensiController',   'kamera'],
    ['POST', '/absensi/proses',     'AbsensiController',   'proses'],
    ['GET',  '/absensi/rekap',      'AbsensiController',   'rekap'],
    ['GET',  '/absensi/rekap/data', 'AbsensiController',   'rekapData'],

    ['GET',  '/absensi-guru',        'AbsensiGuruController', 'index'],
    ['POST', '/absensi-guru/simpan', 'AbsensiGuruController', 'simpan'],
    ['GET',  '/absensi-guru/rekap',  'AbsensiGuruController', 'rekap'],
    ['GET',  '/absensi-guru/dataset',        'AbsensiGuruController', 'dataset'],
    ['POST', '/absensi-guru/dataset/simpan', 'AbsensiGuruController', 'simpanDataset'],
    ['POST', '/absensi-guru/dataset/hapus',  'AbsensiGuruController', 'hapusDataset'],

    ['GET',  '/laporan',            'LaporanController',   'index'],
    ['GET',  '/laporan/pdf',        'LaporanController',   'exportPdf'],
    ['GET',  '/laporan/excel',      'LaporanController',   'exportExcel'],

    ['GET',  '/rpa',                'RpaController',       'index'],
    ['POST', '/rpa/jalankan',       'RpaController',       'jalankan'],

    ['GET',  '/notifikasi/cek',     'NotifikasiController','cek'],
    ['POST', '/notifikasi/baca',    'NotifikasiController','baca'],
];

$cocok = false;
foreach ($rute as [$rutMethod, $rutUri, $controllerNama, $aksi]) {
    if ($method === $rutMethod && $uri === $rutUri) {
        $filePath = __DIR__ . "/app/controller/$controllerNama.php";
        if (!file_exists($filePath)) {
            http_response_code(501);
            echo "Controller $controllerNama belum diimplementasikan.";
            exit;
        }
        require_once $filePath;
        $controller = new $controllerNama();
        $controller->$aksi();
        $cocok = true;
        break;
    }
}

if (!$cocok) {
    http_response_code(404);
    require_once __DIR__ . '/views/404.php';
}
