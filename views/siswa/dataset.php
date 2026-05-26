<?php
$targetFoto  = 10;
$minimalFoto = 5;
$sudahCukup  = $jumlahFoto >= $targetFoto;
$bisaTraining = $jumlahFoto >= $minimalFoto;

$panduan = [
    1  => ['ikon' => '😐', 'label' => 'Frontal',        'instruksi' => 'Hadap kamera lurus, posisi netral.',        'detail' => 'Foto utama. Pastikan wajah terlihat jelas dan pencahayaan merata.'],
    2  => ['ikon' => '↩',  'label' => 'Kiri Ringan',    'instruksi' => 'Putar kepala ±15° ke kiri.',               'detail' => 'Sedikit miring, bukan menoleh jauh. Kedua mata masih terlihat.'],
    3  => ['ikon' => '↪',  'label' => 'Kanan Ringan',   'instruksi' => 'Putar kepala ±15° ke kanan.',              'detail' => 'Posisi cermin dari foto sebelumnya.'],
    4  => ['ikon' => '⬅',  'label' => 'Kiri Jauh',      'instruksi' => 'Putar kepala ±30° ke kiri.',               'detail' => 'Lebih jauh dari foto 2 — hidung hampir sejajar pipi.'],
    5  => ['ikon' => '➡',  'label' => 'Kanan Jauh',     'instruksi' => 'Putar kepala ±30° ke kanan.',              'detail' => 'Posisi cermin dari foto 4.'],
    6  => ['ikon' => '⬆',  'label' => 'Mendongak',      'instruksi' => 'Angkat dagu sedikit ke atas.',             'detail' => 'Jangan terlalu jauh — dagu naik sekitar 10–15°.'],
    7  => ['ikon' => '⬇',  'label' => 'Menunduk',       'instruksi' => 'Turunkan kepala sedikit ke bawah.',        'detail' => 'Tatap kamera dari bawah, dagu sedikit masuk.'],
    8  => ['ikon' => '😊', 'label' => 'Senyum',         'instruksi' => 'Frontal sambil senyum ringan.',            'detail' => 'Ekspresi alami, bukan senyum lebar berlebihan.'],
    9  => ['ikon' => '💡', 'label' => 'Cahaya Samping', 'instruksi' => 'Hadap kamera, cahaya dari samping kiri.',  'detail' => 'Geser sedikit agar satu sisi wajah lebih terang — latih variasi cahaya.'],
    10 => ['ikon' => '↔',  'label' => 'Jarak Berbeda',  'instruksi' => 'Mundur ±20 cm dari posisi biasa.',         'detail' => 'Wajah lebih kecil di frame — variasi jarak meningkatkan robustness.'],
];

$slotAktif = $sudahCukup ? 0 : ($jumlahFoto + 1);
?>

<div class="max-w-3xl">
    <!-- Info siswa -->
    <div class="bg-white border border-slate-200 rounded-lg p-4 mb-6 flex items-center gap-4">
        <?php if (!empty($siswa['foto'])): ?>
        <img src="<?= APP_URL . '/public/' . htmlspecialchars($siswa['foto']) ?>"
             class="w-12 h-12 rounded-full object-cover border border-slate-200" alt="">
        <?php else: ?>
        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-lg font-bold text-slate-500">
            <?= mb_strtoupper(mb_substr($siswa['nama'], 0, 1)) ?>
        </div>
        <?php endif; ?>
        <div>
            <p class="font-semibold text-slate-900"><?= htmlspecialchars($siswa['nama']) ?></p>
            <p class="text-sm text-slate-500">NIS: <?= htmlspecialchars($siswa['nis']) ?> · <?= htmlspecialchars($siswa['nama_kelas']) ?></p>
        </div>
        <div class="ml-auto text-right">
            <p class="text-2xl font-bold <?= $sudahCukup ? 'text-[#15803D]' : ($bisaTraining ? 'text-[#1E40AF]' : 'text-amber-500') ?>">
                <span id="jumlahFoto"><?= $jumlahFoto ?></span>/<?= $targetFoto ?>
            </p>
            <p class="text-xs <?= $sudahCukup ? 'text-green-600 font-medium' : ($bisaTraining ? 'text-blue-600' : 'text-slate-400') ?>">
                <?= $sudahCukup ? '✓ Lengkap' : ($bisaTraining ? 'Cukup (min. '.$minimalFoto.')' : 'foto tersimpan') ?>
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Kamera -->
        <div class="bg-white border border-slate-200 rounded-lg p-5">
            <h2 class="text-sm font-semibold text-slate-700 mb-3">Kamera</h2>

            <!-- Kartu instruksi per foto (diperbarui JS setiap foto tersimpan) -->
            <div id="kartuInstruksi"
                 class="<?= $sudahCukup ? 'hidden ' : '' ?>bg-blue-50 border border-blue-200 rounded-md px-3 py-2.5 mb-3">
                <div class="flex items-start gap-3">
                    <span id="instrIkon" class="text-2xl leading-none flex-shrink-0 mt-0.5">
                        <?= $sudahCukup ? '' : $panduan[$slotAktif]['ikon'] ?>
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 mb-0.5">
                            <span id="instrNomor" class="text-[10px] text-blue-500 font-mono">
                                <?= $sudahCukup ? '' : "Foto $slotAktif dari $targetFoto" ?>
                            </span>
                            <span id="instrJudul" class="text-xs font-semibold text-blue-800">
                                <?= $sudahCukup ? '' : htmlspecialchars($panduan[$slotAktif]['label']) ?>
                            </span>
                        </div>
                        <p id="instrTeks" class="text-xs text-blue-800">
                            <?= $sudahCukup ? '' : htmlspecialchars($panduan[$slotAktif]['instruksi']) ?>
                        </p>
                        <p id="instrDetail" class="text-[10px] text-blue-600 mt-0.5">
                            <?= $sudahCukup ? '' : htmlspecialchars($panduan[$slotAktif]['detail']) ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="relative bg-slate-900 rounded-lg overflow-hidden" style="aspect-ratio:4/3">
                <video id="video" autoplay playsinline muted
                       class="w-full h-full object-cover"
                       style="transform:scaleX(-1)"></video>
                <!-- Panduan posisi wajah -->
                <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
                    <div class="w-36 h-44 rounded-xl"
                         style="border:2px dashed rgba(255,255,255,0.35)"></div>
                </div>
                <div id="statusKamera"
                     class="absolute bottom-3 left-0 right-0 text-center text-xs text-white/80">
                    Memuat kamera...
                </div>
            </div>

            <canvas id="canvas" class="hidden"></canvas>

            <div class="mt-3 flex gap-2">
                <button id="btnAmbil"
                        disabled
                        class="flex-1 h-9 bg-[#1E40AF] hover:bg-[#1D4ED8] disabled:bg-slate-300
                               text-white text-sm font-semibold rounded-md transition-colors">
                    Ambil Foto
                </button>
                <?php if ($jumlahFoto > 0): ?>
                <button type="button" id="btnRetake"
                        onclick="tampilKonfirmasiRetake()"
                        class="h-9 px-3 text-xs text-red-600 border border-red-200 rounded-md hover:bg-red-50 transition-colors">
                    Retake
                </button>
                <?php endif; ?>
            </div>

            <!-- Progress bar -->
            <div class="mt-3">
                <div class="w-full bg-slate-100 rounded-full h-2">
                    <div id="progressBar"
                         class="<?= $sudahCukup ? 'bg-[#15803D]' : ($bisaTraining ? 'bg-[#1E40AF]' : 'bg-amber-400') ?> h-2 rounded-full transition-all"
                         style="width: <?= ($jumlahFoto / $targetFoto) * 100 ?>%"></div>
                    <!-- Penanda minimum 5 foto -->
                    <div class="relative h-0">
                        <div class="absolute top-[-10px] text-[9px] text-slate-400" style="left: 50%">|</div>
                    </div>
                </div>
                <p id="pesanStatus" class="text-xs text-slate-500 mt-1 text-center">
                    <?php if ($sudahCukup): ?>
                        Dataset lengkap. Siap untuk training.
                    <?php elseif ($bisaTraining): ?>
                        Sudah cukup untuk training. Tambah hingga <?= $targetFoto ?> untuk hasil optimal.
                    <?php else: ?>
                        Butuh minimal <?= $minimalFoto ?> foto untuk training (target <?= $targetFoto ?>).
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- Pratinjau foto tersimpan -->
        <div class="bg-white border border-slate-200 rounded-lg p-5">
            <h2 class="text-sm font-semibold text-slate-700 mb-3">Foto Tersimpan</h2>
            <div id="gridFoto" class="grid grid-cols-5 gap-1.5">
                <?php
                $dirDataset = BASE_PATH . '/python/dataset/' . $siswa['nis'] . '/';
                for ($i = 1; $i <= $targetFoto; $i++):
                    $fotoAda  = is_file($dirDataset . "foto_$i.jpg");
                    $isAktif  = !$fotoAda && $i === $slotAktif;
                    $borderSt = $isAktif
                        ? 'border-color:#1E40AF;border-style:solid;background:#EFF6FF;'
                        : '';
                ?>
                <div id="slot-<?= $i ?>"
                     class="aspect-square rounded-md overflow-hidden
                            <?= $fotoAda ? 'border border-green-200 bg-green-50' : 'border border-dashed border-slate-200 bg-slate-50' ?>"
                     <?= $borderSt ? "style=\"$borderSt\"" : '' ?>>
                    <?php if ($fotoAda): ?>
                    <img src="<?= APP_URL ?>/python/dataset/<?= $siswa['nis'] ?>/foto_<?= $i ?>.jpg"
                         class="w-full h-full object-cover" alt="Foto <?= $i ?>">
                    <?php else: ?>
                    <div data-inner class="w-full h-full flex flex-col items-center justify-center gap-0.5"
                         <?= $isAktif ? 'style="color:#1E40AF"' : '' ?>>
                        <span class="text-base leading-none"><?= $panduan[$i]['ikon'] ?></span>
                        <span class="text-[8px] font-medium text-center px-0.5 leading-tight line-clamp-1">
                            <?= htmlspecialchars($panduan[$i]['label']) ?>
                        </span>
                        <span class="text-[8px] font-mono <?= $isAktif ? '' : 'text-slate-300' ?>">
                            <?= $i ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endfor; ?>
            </div>

            <?php if ($sudahCukup): ?>
            <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-md text-sm text-green-800 font-medium">
                ✓ Dataset lengkap. Lanjut ke
                <a href="<?= APP_URL ?>/training" class="underline">Training CNN</a>.
            </div>
            <?php elseif ($bisaTraining): ?>
            <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-md text-sm text-blue-800">
                Dataset cukup (<?= $jumlahFoto ?>/<?= $targetFoto ?>). Bisa
                <a href="<?= APP_URL ?>/training" class="underline font-medium">Training CNN</a>
                sekarang, atau tambah foto untuk hasil lebih optimal.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-4">
        <a href="<?= APP_URL ?>/siswa"
           class="text-sm text-slate-500 hover:text-slate-700">← Kembali ke daftar siswa</a>
    </div>
</div>

<!-- Dialog konfirmasi retake (custom, bukan browser confirm) -->
<div id="dialogRetake"
     class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-sm mx-4 p-6">
        <div class="flex items-start gap-3 mb-4">
            <div class="w-10 h-10 flex-shrink-0 bg-red-100 rounded-full flex items-center justify-center">
                <svg class="w-5 h-5 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-slate-900">Hapus semua foto dataset?</p>
                <p class="text-sm text-slate-500 mt-1">
                    Semua <strong id="konfirmasiJumlah"><?= $jumlahFoto ?></strong> foto
                    <strong><?= htmlspecialchars($siswa['nama']) ?></strong> akan dihapus
                    dari folder dataset. Tindakan ini tidak bisa dibatalkan.
                </p>
            </div>
        </div>
        <div class="flex gap-2 justify-end">
            <button type="button" onclick="tutupKonfirmasiRetake()"
                    class="px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 rounded-md hover:bg-slate-50 transition-colors">
                Batal
            </button>
            <form method="POST" action="<?= APP_URL ?>/siswa/dataset/hapus" class="inline">
                <input type="hidden" name="siswa_id" value="<?= $siswa['id'] ?>">
                <button type="submit"
                        class="px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-md transition-colors">
                    Ya, Hapus Semua
                </button>
            </form>
        </div>
    </div>
</div>

<script>
window.__SISWA_ID__    = <?= (int) $siswa['id'] ?>;
window.__TARGET_FOTO__ = <?= $targetFoto ?>;
window.__MINIMAL_FOTO__= <?= $minimalFoto ?>;
window.__APP_URL__     = '<?= APP_URL ?>';
window.__JUMLAH_AWAL__ = <?= $jumlahFoto ?>;
window.__PANDUAN__     = <?= json_encode($panduan, JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="<?= APP_URL ?>/public/js/dataset.js?v=<?= filemtime(BASE_PATH . '/public/js/dataset.js') ?>"></script>
