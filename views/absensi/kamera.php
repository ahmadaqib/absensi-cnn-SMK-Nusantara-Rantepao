<script>
    window.__APP_URL__    = '<?= APP_URL ?>';
    window.__JADWAL_ID__  = <?= $jadwalId ?>;
    window.__KELAS_ADA_GPS__ = <?= $kelasAdaGps ? 'true' : 'false' ?>;
    window.__RADIUS__ = <?= (int) ($koordinatKelas['radius'] ?? RADIUS_MAKSIMAL) ?>;
</script>

<style>
/* ── Animasi visualisasi pipeline CNN ── */
@keyframes cnnCellPulse {
    0%, 100% { opacity: 0.18; }
    50%       { opacity: 1;    }
}
.cnn-fm-cell.cnn-animasi {
    animation: cnnCellPulse 1.4s ease-in-out infinite;
}
.cnn-stage-box {
    transition: opacity 0.35s ease, border-color 0.35s ease;
    opacity: 0.28;
}
.cnn-stage-box.cnn-aktif {
    opacity: 1;
    border-color: #1E40AF !important;
}
.cnn-arrow { transition: color 0.35s ease; }
.cnn-arrow.cnn-aktif { color: #1E40AF; }
.cnn-bar {
    transition: height 0.5s cubic-bezier(0.34, 1.2, 0.64, 1);
}
.cnn-pool-after {
    transition: opacity 0.55s ease, transform 0.55s ease;
    opacity: 0.1;
    transform: scale(0.75);
}
.cnn-pool-after.cnn-aktif {
    opacity: 1;
    transform: scale(1);
}

/* ── Overlay zona wajah ── */
.korner-bracket {
    position: absolute;
    width: 22px;
    height: 22px;
    border-color: rgba(255,255,255,0.6);
    transition: border-color 0.35s ease, transform 0.35s ease;
}
/* Warna korner berdasarkan data-state */
#zonaWajah[data-state="berhasil"]   .korner-bracket { border-color: #4ADE80; transform: scale(1.12); }
#zonaWajah[data-state="peringatan"] .korner-bracket { border-color: #FCD34D; }
#zonaWajah[data-state="gagal"]      .korner-bracket { border-color: #F87171; }
#zonaWajah[data-state="error"]      .korner-bracket { border-color: #EF4444; }

/* Teks hint */
#hintWajah { transition: opacity 0.3s ease, color 0.3s ease; }
#zonaWajah[data-state="berhasil"]   #hintWajah { color: #4ADE80; }
#zonaWajah[data-state="peringatan"] #hintWajah { color: #FCD34D; }
#zonaWajah[data-state="gagal"]      #hintWajah { color: #F87171; }
#zonaWajah[data-state="error"]      #hintWajah { opacity: 0; }

/* Scanner sweep — hanya muncul saat data-state="mencari" */
#scannerLine { display: none; }
#zonaWajah[data-state="mencari"] #scannerLine { display: block; }
@keyframes scannerSweep {
    0%   { top: 2%;  opacity: 0.9; }
    85%  { opacity: 0.7; }
    100% { top: 96%; opacity: 0; }
}
#scannerLine { animation: scannerSweep 1.9s ease-in-out infinite; }
</style>

<div class="max-w-4xl">

    <!-- Pilih kelas & jadwal -->
    <div class="bg-white border border-slate-200 rounded-lg p-4 mb-5">
        <form method="GET" action="<?= APP_URL ?>/absensi" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Kelas</label>
                <select name="kelas_id" onchange="this.form.submit()"
                        class="h-9 pl-3 pr-8 text-sm border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1E40AF]">
                    <?php foreach ($daftarKelas as $k): ?>
                    <option value="<?= $k['id'] ?>" <?= $k['id'] == $kelasId ? 'selected' : '' ?>>
                        <?= htmlspecialchars($k['nama']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Jadwal Hari Ini</label>
                <?php if ($jadwalHariIni): ?>
                <select name="jadwal_id" onchange="this.form.submit()"
                        class="h-9 pl-3 pr-8 text-sm border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1E40AF]">
                    <?php foreach ($jadwalHariIni as $j): ?>
                    <option value="<?= $j['id'] ?>" <?= $j['id'] == $jadwalId ? 'selected' : '' ?>>
                        <?= substr($j['jam_mulai'], 0, 5) ?>–<?= substr($j['jam_selesai'], 0, 5) ?>
                        · <?= htmlspecialchars($j['mata_pelajaran']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php else: ?>
                <p class="text-sm text-slate-400 h-9 flex items-center">Tidak ada jadwal hari ini.</p>
                <?php endif; ?>
            </div>
            <!-- Status CNN service -->
            <div class="ml-auto flex items-center gap-2">
                <div class="w-2 h-2 rounded-full <?= ($statusCnn['status'] ?? '') === 'aktif' ? 'bg-green-400' : 'bg-red-400' ?>"></div>
                <span class="text-xs text-slate-500">
                    CNN Service <?= ($statusCnn['status'] ?? '') === 'aktif' ? 'aktif' : 'mati' ?>
                </span>
            </div>
        </form>
    </div>

    <!-- Status GPS (dinamis via JS) -->
    <div id="panelGps" class="bg-white border border-slate-200 rounded-lg px-4 py-3 mb-5 flex items-center gap-3">
        <div id="dotGps" class="w-2.5 h-2.5 rounded-full bg-slate-300 flex-shrink-0"></div>
        <p id="pesanGps" class="text-sm text-slate-500">
            <?= $kelasAdaGps
                ? 'Memverifikasi lokasi GPS...'
                : 'Kelas ini belum memiliki koordinat GPS — geofencing tidak aktif.' ?>
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Kamera -->
        <div class="bg-white border border-slate-200 rounded-lg p-5">
            <h2 class="text-sm font-semibold text-slate-700 mb-3">Kamera</h2>

            <?php if (($statusCnn['status'] ?? '') !== 'aktif'): ?>
            <div class="bg-red-50 border border-red-200 rounded-md px-3 py-2 mb-3 text-xs text-red-800">
                CNN service tidak aktif. Jalankan <code class="font-mono">python app.py</code>
                di folder <code class="font-mono">python/</code> terlebih dahulu.
                Cek juga <code class="font-mono">http://127.0.0.1:5000/status</code>.
            </div>
            <?php elseif (!$statusCnn['model_ada']): ?>
            <div class="bg-amber-50 border border-amber-200 rounded-md px-3 py-2 mb-3 text-xs text-amber-800">
                Model belum ada. Lakukan <a href="<?= APP_URL ?>/training" class="underline font-semibold">Training CNN</a> terlebih dahulu.
            </div>
            <?php elseif (array_key_exists('model_siap', $statusCnn) && !$statusCnn['model_siap']): ?>
            <div class="bg-red-50 border border-red-200 rounded-md px-3 py-2 mb-3 text-xs text-red-800">
                Model CNN ada, tetapi belum bisa dimuat: <?= htmlspecialchars($statusCnn['pesan'] ?? 'error tidak diketahui') ?>
            </div>
            <?php endif; ?>

            <?php if (!$jadwalId): ?>
            <div class="bg-slate-50 border border-slate-200 rounded-md px-3 py-2 mb-3 text-xs text-slate-600">
                Pilih kelas dan jadwal untuk memulai absensi.
            </div>
            <?php endif; ?>

            <!-- Feed kamera -->
            <div class="relative bg-slate-900 rounded-xl overflow-hidden" style="aspect-ratio:4/3">
                <video id="videoKamera" autoplay playsinline muted
                       class="w-full h-full object-cover"
                       style="transform:scaleX(-1)"></video>

                <!-- Overlay kamera: dimmer luar zona + korner bracket + scanner -->
                <div class="absolute inset-0 pointer-events-none" id="overlayKamera">

                    <!-- 4 strip gelap di luar zona wajah -->
                    <div class="absolute bg-black/50" style="top:0;left:0;right:0;height:9%"></div>
                    <div class="absolute bg-black/50" style="bottom:0;left:0;right:0;height:17%"></div>
                    <div class="absolute bg-black/50" style="top:9%;bottom:17%;left:0;width:23%"></div>
                    <div class="absolute bg-black/50" style="top:9%;bottom:17%;right:0;width:23%"></div>

                    <!-- Zona wajah: 54% lebar × 74% tinggi, terpusat -->
                    <div id="zonaWajah" data-state="mencari"
                         class="absolute" style="top:9%;left:23%;width:54%;height:74%;">

                        <!-- Korner TL -->
                        <div class="korner-bracket" style="top:0;left:0;
                             border-top:2.5px solid;border-left:2.5px solid;
                             border-radius:5px 0 0 0;"></div>
                        <!-- Korner TR -->
                        <div class="korner-bracket" style="top:0;right:0;
                             border-top:2.5px solid;border-right:2.5px solid;
                             border-radius:0 5px 0 0;"></div>
                        <!-- Korner BL -->
                        <div class="korner-bracket" style="bottom:0;left:0;
                             border-bottom:2.5px solid;border-left:2.5px solid;
                             border-radius:0 0 0 5px;"></div>
                        <!-- Korner BR -->
                        <div class="korner-bracket" style="bottom:0;right:0;
                             border-bottom:2.5px solid;border-right:2.5px solid;
                             border-radius:0 0 5px 0;"></div>

                        <!-- Garis scanner biru (hanya saat state=mencari) -->
                        <div id="scannerLine" class="absolute left-0 right-0"
                             style="height:2px;background:linear-gradient(90deg,
                                    transparent 0%,rgba(96,165,250,0.85) 25%,
                                    rgba(186,230,253,1) 50%,rgba(96,165,250,0.85) 75%,
                                    transparent 100%);"></div>

                        <!-- Teks panduan di bawah dalam zona -->
                        <p id="hintWajah"
                           class="absolute bottom-0 left-0 right-0 text-center
                                  text-[11px] text-white/80 pb-2 tracking-wide">
                            Posisikan wajah dalam kotak
                        </p>
                    </div>
                </div>

                <!-- Indikator REC pojok kanan atas -->
                <div class="absolute top-3 right-3 flex items-center gap-1.5">
                    <div class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></div>
                    <span class="text-[10px] text-white/60 font-mono">REC</span>
                </div>
            </div>

            <canvas id="canvasKamera" class="hidden"></canvas>

            <!-- Status teks -->
            <p id="statusAbsensi" class="text-sm text-center mt-3 text-slate-400">
                Memuat kamera...
            </p>
        </div>

        <!-- Hasil absensi -->
        <div class="bg-white border border-slate-200 rounded-lg p-5">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-slate-700">Absensi Hari Ini</h2>
                <span class="text-xs text-slate-400"><?= date('d M Y') ?></span>
            </div>

            <div id="hasilAbsensi" class="space-y-2 min-h-32">
                <p class="text-xs text-slate-300 text-center pt-10">
                    Hasil absensi akan muncul di sini secara otomatis.
                </p>
            </div>

            <!-- Tabel live (polling 5 detik) -->
            <div class="mt-4 border-t border-slate-100 pt-4">
                <h3 class="text-xs font-semibold text-slate-500 mb-2 uppercase tracking-wide">Terbaru</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="text-slate-400">
                                <th class="text-left pb-1 font-medium">Siswa</th>
                                <th class="text-left pb-1 font-medium">Kelas</th>
                                <th class="text-left pb-1 font-medium">Jam</th>
                                <th class="text-left pb-1 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody id="tabelAbsensiLive">
                            <tr>
                                <td colspan="4" class="text-slate-300 py-3 text-center">Memuat data...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php require_once BASE_PATH . '/views/absensi/_panel_cnn.php'; ?>
</div>

<script src="<?= APP_URL ?>/public/js/absensi.js?v=<?= filemtime(BASE_PATH . '/public/js/absensi.js') ?>"></script>
