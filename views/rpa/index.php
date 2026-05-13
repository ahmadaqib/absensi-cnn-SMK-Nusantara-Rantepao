<div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-base font-semibold text-slate-900">Status UiPath Bot</h2>
            <p class="text-sm text-slate-500">Monitoring proses antrian, notifikasi, dan laporan otomatis.</p>
        </div>
        <form method="POST" action="<?= APP_URL ?>/rpa/jalankan">
            <button type="submit"
                    class="h-9 px-4 bg-[#1E40AF] hover:bg-[#1D4ED8] text-white text-sm font-semibold rounded-md">
                Jalankan Manual
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white border border-slate-200 rounded-lg p-4">
            <p class="text-xs font-medium text-slate-500">Diproses Hari Ini</p>
            <p class="text-2xl font-bold text-slate-900 mt-1"><?= (int) ($statistik['total_diproses'] ?? 0) ?></p>
        </div>
        <div class="bg-white border border-slate-200 rounded-lg p-4">
            <p class="text-xs font-medium text-slate-500">Antrian Pending</p>
            <p class="text-2xl font-bold text-slate-900 mt-1"><?= (int) ($statistik['antrian_pending'] ?? 0) ?></p>
        </div>
        <div class="bg-white border border-slate-200 rounded-lg p-4">
            <p class="text-xs font-medium text-slate-500">Notifikasi Hari Ini</p>
            <p class="text-2xl font-bold text-slate-900 mt-1"><?= (int) ($statistik['notifikasi_hari_ini'] ?? 0) ?></p>
        </div>
        <div class="bg-white border border-slate-200 rounded-lg p-4">
            <p class="text-xs font-medium text-slate-500">Total Laporan</p>
            <p class="text-2xl font-bold text-slate-900 mt-1"><?= (int) ($statistik['total_laporan'] ?? 0) ?></p>
        </div>
    </div>

    <div class="bg-white border border-slate-200 rounded-lg overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-700">Log Bot</h3>
            <span class="text-xs text-slate-400">
                Terakhir aktif: <?= htmlspecialchars($statistik['terakhir_aktif'] ?? '-') ?>
            </span>
        </div>
        <div class="max-h-[520px] overflow-auto bg-slate-950 p-4">
            <?php if (empty($logBot)): ?>
                <p class="text-sm text-slate-400">Belum ada log.</p>
            <?php else: ?>
                <?php foreach ($logBot as $baris): ?>
                    <pre class="text-xs text-slate-200 leading-6 whitespace-pre-wrap"><?= htmlspecialchars($baris) ?></pre>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
