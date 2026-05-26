<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Database — Sistem Absensi CNN</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: 'Inter', sans-serif; background: #F8FAFC; }</style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">
<div class="max-w-lg w-full">

    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
            <svg class="w-5 h-5 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
        </div>
        <div>
            <h1 class="text-lg font-bold text-slate-900">Setup Database Diperlukan</h1>
            <p class="text-sm text-slate-500">Sistem Absensi CNN — SMK Nusantara</p>
        </div>
    </div>

    <?php if ($mysqlMati): ?>
    <!-- MySQL tidak jalan -->
    <div class="bg-white border border-red-200 rounded-xl p-5 mb-4">
        <h2 class="font-semibold text-red-800 mb-2">MySQL belum berjalan</h2>
        <p class="text-sm text-slate-600 mb-3">
            Koneksi ke MySQL gagal. Pastikan MySQL sudah distart sebelum membuka web ini.
        </p>
        <div class="bg-slate-50 rounded-lg p-3 text-sm space-y-1">
            <p class="font-medium text-slate-700">Cara start MySQL:</p>
            <p class="text-slate-500">Windows → Buka XAMPP Control Panel → klik <strong>Start</strong> di baris MySQL</p>
            <p class="text-slate-500">macOS → Buka XAMPP manager-osx → Manage Servers → Start MySQL</p>
        </div>
    </div>

    <?php elseif ($dbBelumAda): ?>
    <!-- Database belum dibuat -->
    <div class="bg-white border border-amber-200 rounded-xl p-5 mb-4">
        <h2 class="font-semibold text-amber-800 mb-2">Database <code class="bg-amber-50 px-1.5 py-0.5 rounded text-sm">sistem_absensi</code> belum ada</h2>
        <p class="text-sm text-slate-600 mb-4">
            Database perlu dibuat dan diisi dengan skema tabel. Ikuti langkah berikut:
        </p>

        <ol class="space-y-4 text-sm">
            <li class="flex gap-3">
                <span class="flex-shrink-0 w-6 h-6 bg-[#1E40AF] text-white rounded-full flex items-center justify-center text-xs font-bold">1</span>
                <div>
                    <p class="font-medium text-slate-800">Buka phpMyAdmin</p>
                    <a href="http://localhost/phpmyadmin" target="_blank"
                       class="text-[#1E40AF] hover:underline">http://localhost/phpmyadmin</a>
                </div>
            </li>
            <li class="flex gap-3">
                <span class="flex-shrink-0 w-6 h-6 bg-[#1E40AF] text-white rounded-full flex items-center justify-center text-xs font-bold">2</span>
                <div>
                    <p class="font-medium text-slate-800">Buat database baru</p>
                    <p class="text-slate-500">Klik <strong>New</strong> → isi nama: <code class="bg-slate-100 px-1 rounded">sistem_absensi</code> → collation: <code class="bg-slate-100 px-1 rounded">utf8mb4_unicode_ci</code> → Create</p>
                </div>
            </li>
            <li class="flex gap-3">
                <span class="flex-shrink-0 w-6 h-6 bg-[#1E40AF] text-white rounded-full flex items-center justify-center text-xs font-bold">3</span>
                <div>
                    <p class="font-medium text-slate-800">Import skema tabel</p>
                    <p class="text-slate-500">Klik database <code class="bg-slate-100 px-1 rounded">sistem_absensi</code> → tab <strong>Import</strong> → pilih file <code class="bg-slate-100 px-1 rounded">database/schema.sql</code> → Go</p>
                </div>
            </li>
            <li class="flex gap-3">
                <span class="flex-shrink-0 w-6 h-6 bg-[#1E40AF] text-white rounded-full flex items-center justify-center text-xs font-bold">4</span>
                <div>
                    <p class="font-medium text-slate-800">Import data awal</p>
                    <p class="text-slate-500">Ulangi Import untuk file <code class="bg-slate-100 px-1 rounded">database/seeder.sql</code></p>
                </div>
            </li>
        </ol>
    </div>

    <?php else: ?>
    <!-- Error lain -->
    <div class="bg-white border border-red-200 rounded-xl p-5 mb-4">
        <h2 class="font-semibold text-red-800 mb-2">Koneksi database gagal</h2>
        <p class="text-sm text-slate-500 font-mono bg-slate-50 p-2 rounded">
            <?= htmlspecialchars($pesanError) ?>
        </p>
        <p class="text-sm text-slate-600 mt-3">
            Cek konfigurasi di <code class="bg-slate-100 px-1 rounded">config/database.php</code>
            — pastikan DB_HOST, DB_USER, dan DB_PASS sudah benar.
        </p>
    </div>
    <?php endif; ?>

    <!-- Tombol coba lagi -->
    <button onclick="location.reload()"
            class="w-full h-10 bg-[#1E40AF] hover:bg-[#1D4ED8] text-white text-sm font-semibold rounded-lg transition-colors">
        Coba Lagi
    </button>

    <p class="text-xs text-slate-400 text-center mt-3">
        Lihat <a href="TUTORIAL.md" class="underline">TUTORIAL.md</a> untuk panduan lengkap.
    </p>
</div>
</body>
</html>
