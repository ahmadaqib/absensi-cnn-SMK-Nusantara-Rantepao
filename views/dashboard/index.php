<?php
$tidakHadir = $ringkasan['total_siswa'] - $ringkasan['hadir'] - $ringkasan['terlambat'];
$pctHadir   = $ringkasan['total_siswa'] > 0
    ? round(($ringkasan['hadir'] + $ringkasan['terlambat']) / $ringkasan['total_siswa'] * 100, 1)
    : 0;
?>

<!-- 4 Card statistik -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

    <!-- Hadir -->
    <div class="bg-white border border-slate-200 rounded-lg p-5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-8 h-8 bg-green-50 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-[#15803D]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
            </div>
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wide">Hadir</span>
        </div>
        <p class="text-3xl font-bold text-slate-900"><?= $ringkasan['hadir'] ?></p>
        <p class="text-xs text-slate-400 mt-1">dari <?= $ringkasan['total_siswa'] ?> siswa</p>
    </div>

    <!-- Terlambat -->
    <div class="bg-white border border-slate-200 rounded-lg p-5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-8 h-8 bg-amber-50 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-[#B45309]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wide">Terlambat</span>
        </div>
        <p class="text-3xl font-bold text-slate-900"><?= $ringkasan['terlambat'] ?></p>
        <p class="text-xs text-slate-400 mt-1">dari <?= $ringkasan['total_siswa'] ?> siswa</p>
    </div>

    <!-- Tidak hadir -->
    <div class="bg-white border border-slate-200 rounded-lg p-5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-8 h-8 bg-red-50 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-[#B91C1C]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </div>
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wide">Tidak Hadir</span>
        </div>
        <p class="text-3xl font-bold text-slate-900"><?= $tidakHadir ?></p>
        <p class="text-xs text-slate-400 mt-1">dari <?= $ringkasan['total_siswa'] ?> siswa</p>
    </div>

    <!-- Persentase kehadiran -->
    <div class="bg-white border border-slate-200 rounded-lg p-5">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4 text-[#1E40AF]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="M18 20V10M12 20V4M6 20v-6"/>
                </svg>
            </div>
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wide">Kehadiran</span>
        </div>
        <p class="text-3xl font-bold text-slate-900"><?= $pctHadir ?>%</p>
        <p class="text-xs text-slate-400 mt-1">tingkat kehadiran hari ini</p>
    </div>
</div>

<!-- Grafik + Tabel -->
<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

    <!-- Grafik kehadiran per kelas -->
    <div class="lg:col-span-2 bg-white border border-slate-200 rounded-lg p-5">
        <h2 class="text-sm font-semibold text-slate-700 mb-4">Kehadiran per Kelas Hari Ini</h2>
        <?php if (!empty($dataGrafik['label'])): ?>
        <canvas id="grafikKelas" height="220"></canvas>
        <?php else: ?>
        <div class="flex items-center justify-center h-40 text-slate-400 text-sm">
            Belum ada data absensi hari ini.
        </div>
        <?php endif; ?>
    </div>

    <!-- Tabel absensi terbaru -->
    <div class="lg:col-span-3 bg-white border border-slate-200 rounded-lg overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-200 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-slate-700">Absensi Terbaru Hari Ini</h2>
            <span class="text-xs text-slate-400"><?= date('d M Y') ?></span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="text-left px-4 py-2.5 font-semibold text-slate-600 text-xs">Siswa</th>
                        <th class="text-left px-4 py-2.5 font-semibold text-slate-600 text-xs">Kelas</th>
                        <th class="text-left px-4 py-2.5 font-semibold text-slate-600 text-xs">Jam</th>
                        <th class="text-left px-4 py-2.5 font-semibold text-slate-600 text-xs">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($absensiTerbaru)): ?>
                    <tr><td colspan="4" class="text-center py-10 text-slate-400">Belum ada absensi hari ini.</td></tr>
                    <?php else: ?>
                    <?php foreach ($absensiTerbaru as $i => $a): ?>
                    <tr class="border-b border-slate-100 <?= $i % 2 !== 0 ? 'bg-slate-50/50' : '' ?>">
                        <td class="px-4 py-2.5">
                            <p class="font-medium text-slate-900"><?= htmlspecialchars($a['nama_siswa']) ?></p>
                            <p class="text-xs text-slate-400"><?= htmlspecialchars($a['nis']) ?></p>
                        </td>
                        <td class="px-4 py-2.5 text-slate-600 text-xs"><?= htmlspecialchars($a['nama_kelas']) ?></td>
                        <td class="px-4 py-2.5 font-mono text-slate-600 text-xs"><?= substr($a['jam'], 0, 5) ?></td>
                        <td class="px-4 py-2.5">
                            <?php
                            $badgeKelas = [
                                'hadir'       => 'badge-hadir',
                                'terlambat'   => 'badge-terlambat',
                                'tidak_hadir' => 'badge-tidak-hadir',
                            ];
                            $labelStatus = [
                                'hadir'       => 'Hadir',
                                'terlambat'   => 'Terlambat',
                                'tidak_hadir' => 'Tidak Hadir',
                            ];
                            ?>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium <?= $badgeKelas[$a['status']] ?? '' ?>">
                                <?= $labelStatus[$a['status']] ?? $a['status'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (!empty($dataGrafik['label'])): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('grafikKelas'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($dataGrafik['label'], JSON_UNESCAPED_UNICODE) ?>,
        datasets: [
            {
                label: 'Hadir',
                data: <?= json_encode($dataGrafik['hadir']) ?>,
                backgroundColor: '#DCFCE7',
                borderColor: '#15803D',
                borderWidth: 1,
                borderRadius: 4,
            },
            {
                label: 'Tidak Hadir',
                data: <?= json_encode($dataGrafik['absen']) ?>,
                backgroundColor: '#FEE2E2',
                borderColor: '#B91C1C',
                borderWidth: 1,
                borderRadius: 4,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 11 } } }
        },
        scales: {
            x: { stacked: false, grid: { display: false } },
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});
</script>
<?php endif; ?>
