<?php
$statusList = [
    ''           => 'Semua Status',
    'hadir'      => 'Hadir',
    'terlambat'  => 'Terlambat',
];
$badgeKelas = [
    'hadir'      => 'badge-hadir',
    'terlambat'  => 'badge-terlambat',
];
$labelStatus = [
    'hadir'      => 'Hadir',
    'terlambat'  => 'Terlambat',
];
$role = Auth::roleSaatIni();
?>

<form method="GET" action="<?= APP_URL ?>/absensi-guru/rekap"
      class="bg-white border border-slate-200 rounded-lg p-4 mb-5 flex flex-wrap gap-4 items-end">

    <?php if ($role !== 'guru'): ?>
    <div>
        <label class="block text-xs font-medium text-slate-600 mb-1">Guru</label>
        <select name="guru_id"
                class="h-9 pl-3 pr-8 text-sm border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1E40AF]">
            <option value="">Semua Guru</option>
            <?php foreach ($daftarGuru as $guru): ?>
            <option value="<?= (int) $guru['id'] ?>" <?= ($filter['guru_id'] ?? 0) == $guru['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($guru['nama']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

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

<div class="bg-white border border-slate-200 rounded-lg overflow-hidden">
    <div class="px-5 py-3.5 border-b border-slate-200 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-700">
            Rekap Kehadiran Guru
            <span class="font-normal text-slate-400 ml-1">(<?= count($dataAbsensi) ?> data)</span>
        </h2>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <th class="text-left px-4 py-3 font-semibold text-slate-600 text-xs">Tanggal</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600 text-xs">Guru</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600 text-xs">Kelas</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600 text-xs">Mata Pelajaran</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600 text-xs">Jam Absen</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600 text-xs">Status</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600 text-xs">Jarak</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dataAbsensi)): ?>
                <tr><td colspan="7" class="text-center py-12 text-slate-400">Tidak ada data absensi guru untuk filter ini.</td></tr>
                <?php else: ?>
                <?php foreach ($dataAbsensi as $i => $a): ?>
                <tr class="border-b border-slate-100 <?= $i % 2 !== 0 ? 'bg-slate-50/50' : '' ?> hover:bg-blue-50/40 transition-colors">
                    <td class="px-4 py-2.5 text-slate-600 text-xs font-mono">
                        <?= date('d/m/Y', strtotime($a['tanggal'])) ?>
                    </td>
                    <td class="px-4 py-2.5">
                        <p class="font-medium text-slate-900"><?= htmlspecialchars($a['nama_guru']) ?></p>
                        <p class="text-xs text-slate-400"><?= htmlspecialchars($a['username']) ?></p>
                    </td>
                    <td class="px-4 py-2.5 text-slate-600 text-xs"><?= htmlspecialchars($a['nama_kelas']) ?></td>
                    <td class="px-4 py-2.5 text-slate-600 text-xs"><?= htmlspecialchars($a['mata_pelajaran']) ?></td>
                    <td class="px-4 py-2.5">
                        <p class="font-mono text-slate-500 text-xs"><?= substr($a['jam'], 0, 5) ?></p>
                        <p class="text-xs text-slate-400">
                            Jadwal <?= substr($a['jam_mulai'], 0, 5) ?> - <?= substr($a['jam_selesai'], 0, 5) ?>
                        </p>
                    </td>
                    <td class="px-4 py-2.5">
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium <?= $badgeKelas[$a['status']] ?? '' ?>">
                            <?= $labelStatus[$a['status']] ?? htmlspecialchars($a['status']) ?>
                        </span>
                    </td>
                    <td class="px-4 py-2.5 text-slate-500 text-xs">
                        <?= $a['jarak_dari_kelas'] !== null ? number_format((float) $a['jarak_dari_kelas'], 1) . ' m' : '-' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
