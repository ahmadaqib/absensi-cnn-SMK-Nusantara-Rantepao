<?php
$statusList = [
    ''            => 'Semua Status',
    'hadir'       => 'Hadir',
    'terlambat'   => 'Terlambat',
    'tidak_hadir' => 'Tidak Hadir',
];
$badgeKelas = [
    'hadir'       => 'badge-hadir',
    'terlambat'   => 'badge-terlambat',
    'tidak_hadir' => 'badge-tidak-hadir',
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

<div class="space-y-5">
    <form method="GET" action="<?= APP_URL ?>/laporan"
          class="bg-white border border-slate-200 rounded-lg p-4 flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Kelas</label>
            <select name="kelas_id" class="h-9 pl-3 pr-8 text-sm border border-slate-300 rounded-md">
                <option value="">Semua Kelas</option>
                <?php foreach ($daftarKelas as $kelas): ?>
                    <option value="<?= $kelas['id'] ?>" <?= ($filter['kelas_id'] ?? 0) == $kelas['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($kelas['nama']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Dari</label>
            <input type="date" name="tanggal_dari" value="<?= htmlspecialchars($filter['tanggal_dari']) ?>"
                   class="h-9 px-3 text-sm border border-slate-300 rounded-md">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Sampai</label>
            <input type="date" name="tanggal_sampai" value="<?= htmlspecialchars($filter['tanggal_sampai']) ?>"
                   class="h-9 px-3 text-sm border border-slate-300 rounded-md">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
            <select name="status" class="h-9 pl-3 pr-8 text-sm border border-slate-300 rounded-md">
                <?php foreach ($statusList as $value => $label): ?>
                    <option value="<?= $value ?>" <?= ($filter['status'] ?? '') === $value ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="h-9 px-5 bg-[#1E40AF] hover:bg-[#1D4ED8] text-white text-sm font-semibold rounded-md">
            Tampilkan
        </button>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white border border-slate-200 rounded-lg p-4"><p class="text-xs text-slate-500">Total</p><p class="text-2xl font-bold"><?= (int) $ringkasan['total'] ?></p></div>
        <div class="bg-white border border-slate-200 rounded-lg p-4"><p class="text-xs text-slate-500">Hadir</p><p class="text-2xl font-bold text-green-700"><?= (int) $ringkasan['hadir'] ?></p></div>
        <div class="bg-white border border-slate-200 rounded-lg p-4"><p class="text-xs text-slate-500">Terlambat</p><p class="text-2xl font-bold text-amber-700"><?= (int) $ringkasan['terlambat'] ?></p></div>
        <div class="bg-white border border-slate-200 rounded-lg p-4"><p class="text-xs text-slate-500">Tidak Hadir</p><p class="text-2xl font-bold text-red-700"><?= (int) $ringkasan['tidak_hadir'] ?></p></div>
    </div>

    <div class="bg-white border border-slate-200 rounded-lg overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-sm font-semibold text-slate-700">Data Laporan (<?= count($dataAbsensi) ?>)</h2>
            <div class="flex gap-2">
                <a href="<?= APP_URL ?>/laporan/pdf?<?= http_build_query($filter) ?>" class="h-8 px-3 flex items-center text-xs font-medium text-red-600 border border-red-200 rounded hover:bg-red-50">Export PDF</a>
                <a href="<?= APP_URL ?>/laporan/excel?<?= http_build_query($filter) ?>" class="h-8 px-3 flex items-center text-xs font-medium text-green-700 border border-green-200 rounded hover:bg-green-50">Export XLSX</a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead><tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600">Tanggal</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600">Siswa</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600">Kelas</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600">Mata Pelajaran</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600">Jam</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600">Status</th>
                    <th class="text-left px-4 py-3 text-xs font-semibold text-slate-600">Proses</th>
                </tr></thead>
                <tbody>
                <?php if (empty($dataAbsensi)): ?>
                    <tr><td colspan="7" class="text-center py-12 text-slate-400">Tidak ada data untuk filter ini.</td></tr>
                <?php else: ?>
                    <?php foreach ($dataAbsensi as $i => $row): ?>
                        <?php $proses = $row['status_antrian'] ?? 'FINAL'; ?>
                        <tr class="border-b border-slate-100 <?= $i % 2 ? 'bg-slate-50/50' : '' ?>">
                            <td class="px-4 py-2.5 text-xs text-slate-600"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                            <td class="px-4 py-2.5"><p class="font-medium text-slate-900"><?= htmlspecialchars($row['nama_siswa']) ?></p><p class="text-xs text-slate-400"><?= htmlspecialchars($row['nis']) ?></p></td>
                            <td class="px-4 py-2.5 text-xs text-slate-600"><?= htmlspecialchars($row['nama_kelas']) ?></td>
                            <td class="px-4 py-2.5 text-xs text-slate-600"><?= htmlspecialchars($row['mata_pelajaran']) ?></td>
                            <td class="px-4 py-2.5 text-xs font-mono text-slate-500"><?= htmlspecialchars(substr($row['jam'], 0, 5)) ?></td>
                            <td class="px-4 py-2.5"><span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium <?= $badgeKelas[$row['status']] ?? '' ?>"><?= htmlspecialchars(str_replace('_', ' ', $row['status'])) ?></span></td>
                            <td class="px-4 py-2.5"><span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium <?= $badgeProses[$proses] ?? 'bg-slate-50 text-slate-600 border border-slate-200' ?>"><?= htmlspecialchars($labelProses[$proses] ?? $proses) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
