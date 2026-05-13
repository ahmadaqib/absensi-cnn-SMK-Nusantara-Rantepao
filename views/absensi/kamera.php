<script>
    window.__APP_URL__   = '<?= APP_URL ?>';
    window.__JADWAL_ID__ = <?= $jadwalId ?>;
</script>

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

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Kamera -->
        <div class="bg-white border border-slate-200 rounded-lg p-5">
            <h2 class="text-sm font-semibold text-slate-700 mb-3">Kamera</h2>

            <?php if (($statusCnn['status'] ?? '') !== 'aktif'): ?>
            <div class="bg-red-50 border border-red-200 rounded-md px-3 py-2 mb-3 text-xs text-red-800">
                CNN service tidak aktif. Jalankan <code class="font-mono">python app.py</code>
                di folder <code class="font-mono">python/</code> terlebih dahulu.
            </div>
            <?php elseif (!$statusCnn['model_ada']): ?>
            <div class="bg-amber-50 border border-amber-200 rounded-md px-3 py-2 mb-3 text-xs text-amber-800">
                Model belum ada. Lakukan <a href="<?= APP_URL ?>/training" class="underline font-semibold">Training CNN</a> terlebih dahulu.
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
                       class="w-full h-full object-cover"></video>

                <!-- Overlay bingkai wajah -->
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div id="bingkaiWajah"
                         class="w-44 h-52 border-2 border-dashed border-white/50 rounded-2xl transition-colors duration-300"></div>
                </div>

                <!-- Indikator scanning -->
                <div class="absolute top-3 right-3">
                    <div class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse"></div>
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
                <!-- Kartu hasil absensi diisi oleh JS -->
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
</div>

<script src="<?= APP_URL ?>/public/js/absensi.js"></script>
