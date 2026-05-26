<?php
$statusList = [
    ''           => 'Semua Status',
    'hadir'      => 'Hadir',
    'terlambat'  => 'Terlambat',
    'tidak_hadir'=> 'Tidak Hadir',
];
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
$labelProses = [
    'FINAL'      => 'Tersimpan',
    'DONE'       => 'Tersimpan',
    'PENDING'    => 'Menunggu RPA',
    'PROCESSING' => 'Diproses RPA',
];
$badgeProses = [
    'FINAL'      => 'bg-green-50 text-green-700 border border-green-200',
    'DONE'       => 'bg-green-50 text-green-700 border border-green-200',
    'PENDING'    => 'bg-amber-50 text-amber-700 border border-amber-200',
    'PROCESSING' => 'bg-blue-50 text-blue-700 border border-blue-200',
];
?>

<!-- Filter -->
<form method="GET" action="<?= APP_URL ?>/absensi/rekap"
      class="bg-white border border-slate-200 rounded-lg p-4 mb-5 flex flex-wrap gap-4 items-end">

    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Kelas</label>
        <select name="kelas_id"
                class="h-9 pl-3 pr-8 text-sm border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1E40AF]">
            <option value="">Semua Kelas</option>
            <?php foreach ($daftarKelas as $k): ?>
            <option value="<?= $k['id'] ?>" <?= ($filter['kelas_id'] ?? 0) == $k['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($k['nama']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Dari Tanggal</label>
        <input type="date" name="tanggal_dari"
               value="<?= htmlspecialchars($filter['tanggal_dari'] ?? date('Y-m-d')) ?>"
               class="h-9 px-3 text-sm border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1E40AF]">
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Sampai Tanggal</label>
        <input type="date" name="tanggal_sampai"
               value="<?= htmlspecialchars($filter['tanggal_sampai'] ?? date('Y-m-d')) ?>"
               class="h-9 px-3 text-sm border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1E40AF]">
    </div>

    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
        <select name="status"
                class="h-9 pl-3 pr-8 text-sm border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1E40AF]">
            <?php foreach ($statusList as $val => $label): ?>
            <option value="<?= $val ?>" <?= ($filter['status'] ?? '') === $val ? 'selected' : '' ?>>
                <?= $label ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <button type="submit"
            class="h-9 px-5 bg-[#1E40AF] hover:bg-[#1D4ED8] text-white text-sm font-semibold rounded-md transition-colors">
        Tampilkan
    </button>
</form>

<!-- Ringkasan & Tabel -->
<div class="bg-white border border-slate-200 rounded-lg overflow-hidden">
    <div class="px-5 py-3.5 border-b border-slate-200 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-700">
            Hasil Rekap
            <span class="font-normal text-slate-400 ml-1">(<?= count($dataAbsensi) ?> data)</span>
        </h2>
        <div class="flex gap-2">
            <a href="<?= APP_URL ?>/laporan/pdf?<?= http_build_query($filter) ?>"
               class="h-8 px-3 flex items-center text-xs font-medium text-red-600 border border-red-200 rounded hover:bg-red-50 transition-colors">
                Export PDF
            </a>
            <a href="<?= APP_URL ?>/laporan/excel?<?= http_build_query($filter) ?>"
               class="h-8 px-3 flex items-center text-xs font-medium text-green-700 border border-green-200 rounded hover:bg-green-50 transition-colors">
                Export XLSX
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left px-4 py-3 font-semibold text-slate-600 text-xs">Tanggal</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600 text-xs">Siswa</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600 text-xs">Kelas</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600 text-xs">Mata Pelajaran</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600 text-xs">Jam</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600 text-xs">Status</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600 text-xs">Proses</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600 text-xs">Confidence</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dataAbsensi)): ?>
                <tr><td colspan="8" class="text-center py-12 text-slate-400">Tidak ada data untuk filter ini.</td></tr>
                <?php else: ?>
                <?php foreach ($dataAbsensi as $i => $a): ?>
                <?php $proses = $a['status_antrian'] ?? 'FINAL'; ?>
                <tr class="border-b border-slate-100 <?= $i % 2 !== 0 ? 'bg-slate-50/50' : '' ?> hover:bg-blue-50/40 transition-colors">
                    <td class="px-4 py-2.5 text-slate-600 text-xs font-mono">
                        <?= date('d/m/Y', strtotime($a['tanggal'])) ?>
                    </td>
                    <td class="px-4 py-2.5">
                        <p class="font-medium text-slate-900"><?= htmlspecialchars($a['nama_siswa']) ?></p>
                        <p class="text-xs text-slate-400"><?= htmlspecialchars($a['nis']) ?></p>
                    </td>
                    <td class="px-4 py-2.5 text-slate-600 text-xs"><?= htmlspecialchars($a['nama_kelas']) ?></td>
                    <td class="px-4 py-2.5 text-slate-600 text-xs"><?= htmlspecialchars($a['mata_pelajaran']) ?></td>
                    <td class="px-4 py-2.5 font-mono text-slate-500 text-xs"><?= substr($a['jam'], 0, 5) ?></td>
                    <td class="px-4 py-2.5">
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium <?= $badgeKelas[$a['status']] ?? '' ?>">
                            <?= $labelStatus[$a['status']] ?? $a['status'] ?>
                        </span>
                    </td>
                    <td class="px-4 py-2.5">
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium <?= $badgeProses[$proses] ?? 'bg-slate-50 text-slate-600 border border-slate-200' ?>">
                            <?= $labelProses[$proses] ?? $proses ?>
                        </span>
                    </td>
                    <td class="px-4 py-2.5 text-slate-400 text-xs font-mono">
                        <?= $a['confidence'] !== null ? number_format($a['confidence'] * 100, 1) . '%' : '—' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
