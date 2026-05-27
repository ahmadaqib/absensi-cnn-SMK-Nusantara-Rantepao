<?php
$role       = Auth::roleSaatIni();
$appPath  = parse_url(APP_URL, PHP_URL_PATH) ?? '';
$uriAktif = '/' . trim(str_replace($appPath, '', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/'), '/');

function itemNav(string $href, string $label, string $uriAktif, string $ikon): void {
    $harusEksak = in_array($href, ['/dashboard', '/logout', '/absensi', '/absensi-guru'], true);
    $aktif = $harusEksak ? $uriAktif === $href : str_starts_with($uriAktif, $href);
    $kelas = $aktif
        ? 'bg-blue-50 text-primer font-semibold'
        : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900';
    echo "<a href=\"" . APP_URL . $href . "\"
             class=\"flex items-center gap-3 px-3 py-2 rounded-md text-sm transition-colors $kelas\">
            <span class=\"w-4 h-4 flex-shrink-0\">$ikon</span>
            <span class=\"truncate\">$label</span>
          </a>";
}

function judulGrupNav(string $label): void {
    echo "<p class=\"px-3 pt-4 pb-1.5 text-[11px] font-semibold uppercase tracking-wide text-slate-400 first:pt-0\">$label</p>";
}

$ikonDashboard = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>';
$ikonAbsensi   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="4"/><path d="M6 20v-2a4 4 0 0 1 8 0v2"/><path d="M18 8h4M20 6v4"/></svg>';
$ikonSiswa     = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>';
$ikonKelas     = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>';
$ikonJadwal    = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
$ikonLaporan   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>';
$ikonTraining  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>';
$ikonRpa       = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="8" width="18" height="10" rx="2"/><path d="M12 8V4"/><circle cx="8" cy="13" r="1"/><circle cx="16" cy="13" r="1"/><path d="M9 18v2M15 18v2"/></svg>';
$ikonPanduan   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 4.5A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5z"/><path d="M8 7h8M8 11h8M8 15h5"/></svg>';
$ikonPengaturan = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.6-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3h.1a1.7 1.7 0 0 0 .9-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9v.1a1.7 1.7 0 0 0 1.5.9H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5.9z"/></svg>';
$ikonKeluar    = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>';
?>

<!-- Sidebar -->
<aside class="w-64 flex-shrink-0 bg-white border-r border-slate-200 flex flex-col h-full">
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
    <nav class="flex-1 overflow-y-auto px-3 py-4">
        <?php judulGrupNav('Utama'); ?>
        <?php itemNav('/dashboard', 'Dashboard', $uriAktif, $ikonDashboard); ?>

        <?php if ($role === 'admin'): ?>
            <?php judulGrupNav('Data Master'); ?>
            <?php itemNav('/siswa',    'Kelola Siswa',  $uriAktif, $ikonSiswa); ?>
            <?php itemNav('/kelas',    'Kelola Kelas',  $uriAktif, $ikonKelas); ?>
            <?php itemNav('/jadwal',   'Jadwal',        $uriAktif, $ikonJadwal); ?>

            <?php judulGrupNav('Operasional'); ?>
            <?php itemNav('/absensi', 'Absensi Kamera', $uriAktif, $ikonAbsensi); ?>
            <?php itemNav('/absensi/rekap', 'Rekap Siswa', $uriAktif, $ikonAbsensi); ?>
            <?php itemNav('/absensi-guru/rekap', 'Rekap Guru', $uriAktif, $ikonAbsensi); ?>
            <?php itemNav('/laporan', 'Laporan', $uriAktif, $ikonLaporan); ?>

            <?php judulGrupNav('Sistem'); ?>
            <?php itemNav('/training', 'Training CNN',  $uriAktif, $ikonTraining); ?>
            <?php itemNav('/rpa',      'RPA Bot',       $uriAktif, $ikonRpa); ?>
            <?php itemNav('/pengaturan', 'Pengaturan',  $uriAktif, $ikonPengaturan); ?>

            <?php judulGrupNav('Bantuan'); ?>
            <?php itemNav('/panduan',  'Panduan',       $uriAktif, $ikonPanduan); ?>
        <?php endif; ?>

        <?php if ($role === 'guru'): ?>
            <?php judulGrupNav('Absensi'); ?>
            <?php itemNav('/absensi', 'Absensi Kamera', $uriAktif, $ikonAbsensi); ?>
            <?php itemNav('/absensi-guru', 'Absensi Guru', $uriAktif, $ikonAbsensi); ?>

            <?php judulGrupNav('Data Wajah'); ?>
            <?php itemNav('/absensi-guru/dataset', 'Dataset Wajah', $uriAktif, $ikonTraining); ?>

            <?php judulGrupNav('Rekap & Laporan'); ?>
            <?php itemNav('/absensi/rekap', 'Rekap Siswa', $uriAktif, $ikonAbsensi); ?>
            <?php itemNav('/absensi-guru/rekap', 'Rekap Guru', $uriAktif, $ikonAbsensi); ?>
            <?php itemNav('/laporan', 'Laporan', $uriAktif, $ikonLaporan); ?>
        <?php endif; ?>

        <?php if ($role === 'kepala_sekolah'): ?>
            <?php judulGrupNav('Monitoring'); ?>
            <?php itemNav('/absensi/rekap', 'Rekap Siswa', $uriAktif, $ikonAbsensi); ?>
            <?php itemNav('/absensi-guru/rekap', 'Rekap Guru', $uriAktif, $ikonAbsensi); ?>
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
    <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 flex-shrink-0">
        <h1 class="text-lg font-semibold text-slate-900"><?= htmlspecialchars($judulHalaman ?? '') ?></h1>
        <div class="relative">
            <button type="button" id="btnNotifikasi"
                    class="relative w-9 h-9 inline-flex items-center justify-center rounded-md border border-slate-200 text-slate-600 hover:bg-slate-50"
                    aria-label="Notifikasi">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 7h18s-3 0-3-7"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <span id="badgeNotifikasi"
                      class="hidden absolute -top-1 -right-1 min-w-5 h-5 px-1 rounded-full bg-red-600 text-white text-[11px] leading-5 text-center font-semibold">0</span>
            </button>
            <div id="panelNotifikasi"
                 class="hidden absolute right-0 mt-2 w-80 bg-white border border-slate-200 rounded-lg shadow-lg z-40 overflow-hidden">
                <div class="px-4 py-3 border-b border-slate-200 flex items-center justify-between">
                    <p class="text-sm font-semibold text-slate-800">Notifikasi</p>
                    <button type="button" id="btnBacaNotifikasi" class="text-xs font-medium text-[#1E40AF]">Tandai dibaca</button>
                </div>
                <div id="daftarNotifikasi" class="max-h-80 overflow-y-auto">
                    <p class="px-4 py-6 text-sm text-slate-400 text-center">Memuat notifikasi...</p>
                </div>
            </div>
        </div>
    </header>
    <main class="flex-1 overflow-y-auto p-6">
<script>
(() => {
    const btn = document.getElementById('btnNotifikasi');
    const panel = document.getElementById('panelNotifikasi');
    const badge = document.getElementById('badgeNotifikasi');
    const daftar = document.getElementById('daftarNotifikasi');
    const btnBaca = document.getElementById('btnBacaNotifikasi');
    if (!btn || !panel || !badge || !daftar || !btnBaca) return;

    const render = (payload) => {
        const jumlah = Number(payload.jumlah || 0);
        badge.textContent = jumlah > 99 ? '99+' : String(jumlah);
        badge.classList.toggle('hidden', jumlah === 0);
        const data = Array.isArray(payload.data) ? payload.data : [];
        if (data.length === 0) {
            daftar.innerHTML = '<p class="px-4 py-6 text-sm text-slate-400 text-center">Belum ada notifikasi.</p>';
            return;
        }
        daftar.innerHTML = data.map((item) => {
            const kuat = Number(item.dibaca) === 0 ? 'font-semibold text-slate-900' : 'text-slate-600';
            return `<div class="px-4 py-3 border-b border-slate-100">
                <p class="text-sm ${kuat}">${escapeHtml(item.pesan || '')}</p>
                <p class="text-xs text-slate-400 mt-1">${escapeHtml(item.dibuat_pada || '')}</p>
            </div>`;
        }).join('');
    };

    const escapeHtml = (value) => String(value)
        .replaceAll('&', '&amp;').replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;').replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const muat = () => fetch('<?= APP_URL ?>/notifikasi/cek', { headers: { 'Accept': 'application/json' } })
        .then((res) => res.ok ? res.json() : { jumlah: 0, data: [] })
        .then(render)
        .catch(() => {});

    btn.addEventListener('click', () => {
        panel.classList.toggle('hidden');
        if (!panel.classList.contains('hidden')) muat();
    });
    btnBaca.addEventListener('click', () => {
        fetch('<?= APP_URL ?>/notifikasi/baca', { method: 'POST', headers: { 'Accept': 'application/json' } })
            .then((res) => res.ok ? res.json() : null)
            .then(() => muat())
            .catch(() => {});
    });
    document.addEventListener('click', (event) => {
        if (!panel.contains(event.target) && !btn.contains(event.target)) panel.classList.add('hidden');
    });
    muat();
    setInterval(muat, 20000);
})();
</script>
