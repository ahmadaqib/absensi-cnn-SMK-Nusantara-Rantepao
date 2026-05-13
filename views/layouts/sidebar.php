<?php
$role       = Auth::roleSaatIni();
$appPath  = parse_url(APP_URL, PHP_URL_PATH) ?? '';
$uriAktif = '/' . trim(str_replace($appPath, '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/'), '/');

function itemNav(string $href, string $label, string $uriAktif, string $ikon): void {
    $aktif = str_starts_with($uriAktif, $href) && $href !== '/dashboard'
           ? true
           : $uriAktif === $href;
    $kelas = $aktif
        ? 'bg-blue-50 text-primer font-semibold'
        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900';
    echo "<a href=\"" . APP_URL . $href . "\"
             class=\"flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-colors $kelas\">
            <span class=\"w-4 h-4 flex-shrink-0\">$ikon</span>
            $label
          </a>";
}

$ikonDashboard = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>';
$ikonAbsensi   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M6 20v-2a4 4 0 0 1 8 0v2"/><path d="M18 8h4M20 6v4"/></svg>';
$ikonSiswa     = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
$ikonKelas     = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>';
$ikonJadwal    = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
$ikonLaporan   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>';
$ikonTraining  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>';
$ikonKeluar    = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>';
?>

<!-- Sidebar -->
<aside class="w-60 flex-shrink-0 bg-white border-r border-slate-200 flex flex-col h-full">
    <!-- Logo -->
    <div class="h-16 flex items-center px-4 border-b border-slate-200">
        <div class="w-8 h-8 bg-primer rounded-lg flex items-center justify-center mr-3">
            <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
            </svg>
        </div>
        <div>
            <p class="text-sm font-bold text-slate-900 leading-tight">SMK Nusantara</p>
            <p class="text-xs text-slate-500">Sistem Absensi</p>
        </div>
    </div>

    <!-- Menu navigasi -->
    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
        <?php itemNav('/dashboard', 'Dashboard', $uriAktif, $ikonDashboard); ?>

        <?php if ($role === 'admin'): ?>
            <?php itemNav('/siswa',    'Kelola Siswa',  $uriAktif, $ikonSiswa); ?>
            <?php itemNav('/kelas',    'Kelola Kelas',  $uriAktif, $ikonKelas); ?>
            <?php itemNav('/jadwal',   'Jadwal',        $uriAktif, $ikonJadwal); ?>
            <?php itemNav('/training', 'Training CNN',  $uriAktif, $ikonTraining); ?>
        <?php endif; ?>

        <?php if (in_array($role, ['admin', 'guru'])): ?>
            <?php itemNav('/absensi/rekap', 'Rekap Absensi', $uriAktif, $ikonAbsensi); ?>
        <?php endif; ?>

        <?php if (in_array($role, ['admin', 'guru', 'kepala_sekolah'])): ?>
            <?php itemNav('/laporan', 'Laporan', $uriAktif, $ikonLaporan); ?>
        <?php endif; ?>
    </nav>

    <!-- Info pengguna + logout -->
    <div class="border-t border-slate-200 px-3 py-3 space-y-1">
        <div class="px-3 py-2">
            <p class="text-sm font-medium text-slate-900 truncate"><?= htmlspecialchars(Auth::namaSaatIni()) ?></p>
            <p class="text-xs text-slate-500 capitalize"><?= str_replace('_', ' ', Auth::roleSaatIni()) ?></p>
        </div>
        <?php itemNav('/logout', 'Keluar', $uriAktif, $ikonKeluar); ?>
    </div>
</aside>

<!-- Konten utama -->
<div class="flex-1 flex flex-col overflow-hidden">
    <!-- Top bar -->
    <header class="h-16 bg-white border-b border-slate-200 flex items-center px-6 flex-shrink-0">
        <h1 class="text-lg font-semibold text-slate-900"><?= htmlspecialchars($judulHalaman ?? '') ?></h1>
    </header>
    <main class="flex-1 overflow-y-auto p-6">
