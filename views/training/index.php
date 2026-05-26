<?php
$modelAda    = file_exists(BASE_PATH . '/python/model_absensi.h5');
$labelAda    = file_exists(BASE_PATH . '/python/label_map.json');
$sedangJalan = in_array($statusTraining['status'] ?? '', ['mulai', 'berjalan']);
$selesai     = ($statusTraining['status'] ?? '') === 'selesai';
$error       = ($statusTraining['status'] ?? '') === 'error';
?>

<div class="max-w-3xl space-y-6">

    <!-- Status model saat ini -->
    <div class="bg-white border border-slate-200 rounded-lg p-5">
        <h2 class="text-sm font-semibold text-slate-700 mb-4">Status Model</h2>
        <div class="grid grid-cols-2 gap-4">
            <div class="flex items-center gap-3">
                <div class="w-2.5 h-2.5 rounded-full <?= $modelAda ? 'bg-green-500' : 'bg-slate-300' ?>"></div>
                <span class="text-sm text-slate-700">
                    Model CNN (model_absensi.h5)
                    <span class="text-slate-400"><?= $modelAda ? '— tersedia' : '— belum ada' ?></span>
                </span>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-2.5 h-2.5 rounded-full <?= $labelAda ? 'bg-green-500' : 'bg-slate-300' ?>"></div>
                <span class="text-sm text-slate-700">
                    Label Map (label_map.json)
                    <span class="text-slate-400"><?= $labelAda ? '— tersedia' : '— belum ada' ?></span>
                </span>
            </div>
        </div>
    </div>

    <!-- Info dataset -->
    <div class="bg-white border border-slate-200 rounded-lg p-5">
        <h2 class="text-sm font-semibold text-slate-700 mb-3">Dataset Tersedia</h2>
        <?php if (empty($infoDataset['detail'])): ?>
        <p class="text-sm text-slate-400">Belum ada dataset. Capture foto wajah siswa terlebih dahulu.</p>
        <?php else: ?>
        <div class="space-y-1.5">
            <?php foreach ($infoDataset['detail'] as $d): ?>
            <div class="flex items-center gap-3 text-sm">
                <div class="w-2 h-2 rounded-full flex-shrink-0 <?= $d['cukup'] ? 'bg-green-400' : 'bg-amber-400' ?>"></div>
                <span class="font-mono text-slate-600 w-24"><?= htmlspecialchars($d['nis']) ?></span>
                <span class="text-slate-500"><?= $d['jumlah_foto'] ?> foto</span>
                <?php if (!$d['cukup']): ?>
                <span class="text-xs text-amber-600">(butuh min. 5)</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <p class="text-xs text-slate-400 mt-3">
            <?= $infoDataset['jumlah_siswa'] ?> siswa siap untuk training (≥5 foto).
        </p>
        <?php endif; ?>
    </div>

    <!-- Panel training -->
    <div class="bg-white border border-slate-200 rounded-lg p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-slate-700">Training Model</h2>
            <?php if (!$sedangJalan): ?>
            <form method="POST" action="<?= APP_URL ?>/training/mulai">
                <button type="submit"
                        <?= $infoDataset['jumlah_siswa'] < 2 ? 'disabled' : '' ?>
                        class="h-9 px-5 bg-[#1E40AF] hover:bg-[#1D4ED8] disabled:bg-slate-300
                               text-white text-sm font-semibold rounded-md transition-colors">
                    Mulai Training
                </button>
            </form>
            <?php endif; ?>
        </div>

        <!-- Progress bar -->
        <div class="mb-3">
            <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                <span id="labelProgres"><?= htmlspecialchars($statusTraining['pesan'] ?? 'Siap.') ?></span>
                <span id="angkaProgres"><?= $statusTraining['progres'] ?? 0 ?>%</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-3">
                <div id="progressBar"
                     class="h-3 rounded-full transition-all duration-500
                            <?= $selesai ? 'bg-green-500' : ($error ? 'bg-red-500' : 'bg-[#1E40AF]') ?>"
                     style="width: <?= $statusTraining['progres'] ?? 0 ?>%"></div>
            </div>
        </div>

        <!-- Hasil training -->
        <?php if ($selesai && $statusTraining['akurasi']): ?>
        <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-sm font-semibold text-green-800">Training Berhasil</p>
            <p class="text-3xl font-bold text-green-700 mt-1"><?= $statusTraining['akurasi'] ?>%</p>
            <p class="text-xs text-green-600 mt-0.5">akurasi validasi</p>
            <p class="text-xs text-green-600 mt-2">
                <?= htmlspecialchars($statusTraining['waktu'] ?? '') ?>
            </p>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
            <p class="font-semibold">Training Gagal</p>
            <p class="mt-1"><?= htmlspecialchars($statusTraining['error'] ?? '') ?></p>
        </div>
        <?php endif; ?>

        <?php if ($sedangJalan): ?>
        <div class="mt-3 flex items-center gap-2 text-sm text-slate-500">
            <svg class="w-4 h-4 animate-spin text-[#1E40AF]" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" stroke-dasharray="30 60"/>
            </svg>
            Training berjalan... halaman diperbarui otomatis setiap 3 detik.
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($sedangJalan): ?>
<script>
// Polling status training setiap 3 detik
function pollingStatus() {
    fetch('<?= APP_URL ?>/training/status')
        .then(r => r.json())
        .then(data => {
            document.getElementById('labelProgres').textContent = data.pesan  || '';
            document.getElementById('angkaProgres').textContent = (data.progres || 0) + '%';
            document.getElementById('progressBar').style.width  = (data.progres || 0) + '%';

            if (data.status === 'selesai' || data.status === 'error') {
                location.reload();
            } else {
                setTimeout(pollingStatus, 3000);
            }
        })
        .catch(() => setTimeout(pollingStatus, 5000));
}
setTimeout(pollingStatus, 3000);
</script>
<?php endif; ?>
