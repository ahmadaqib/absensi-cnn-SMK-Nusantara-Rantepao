<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helper/XlsxWriter.php';
require_once __DIR__ . '/ProsesAbsensi.php';
require_once __DIR__ . '/TelegramBot.php';
require_once __DIR__ . '/KirimNotifikasi.php';
require_once __DIR__ . '/GenerateLaporan.php';
require_once __DIR__ . '/UiPathBot.php';

$bot = new UiPathBot(koneksiDB());
$bot->jalankan();
