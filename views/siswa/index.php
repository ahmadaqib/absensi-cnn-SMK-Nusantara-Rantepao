<?php
$kelasIdFilter = isset($_GET['kelas_id']) ? (int) $_GET['kelas_id'] : 0;

// Hitung dataset per siswa (cek folder python/dataset/[nis]/)
$datasetInfo = [];
foreach ($daftarSiswa as $s) {
    $dir = BASE_PATH . '/python/dataset/' . $s['nis'] . '/';
    $datasetInfo[$s['nis']] = is_dir($dir) ? count(glob($dir . '*.jpg')) : 0;
}
?>

<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <form method="GET" action="<?= APP_URL ?>/siswa" class="flex items-center gap-2">
            <select name="kelas_id" onchange="this.form.submit()"
                    class="h-9 pl-3 pr-8 text-sm border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1E40AF]">
                <option value="">Semua Kelas</option>
                <?php foreach ($daftarKelas as $k): ?>
                <option value="<?= $k['id'] ?>" <?= $k['id'] == $kelasIdFilter ? 'selected' : '' ?>>
                    <?= htmlspecialchars($k['nama']) ?> (<?= $k['tahun'] ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </form>
        <span class="text-sm text-slate-500"><?= count($daftarSiswa) ?> siswa</span>
    </div>
    <a href="<?= APP_URL ?>/siswa/tambah"
       class="inline-flex items-center gap-2 h-9 px-4 bg-[#1E40AF] hover:bg-[#1D4ED8] text-white text-sm font-semibold rounded-md transition-colors">
        + Tambah Siswa
    </a>
</div>

<div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-slate-50 border-b border-slate-200">
                <th class="text-left px-4 py-3 font-semibold text-slate-700">NIS</th>
                <th class="text-left px-4 py-3 font-semibold text-slate-700">Nama</th>
                <th class="text-left px-4 py-3 font-semibold text-slate-700">Kelas</th>
                <th class="text-left px-4 py-3 font-semibold text-slate-700">Status</th>
                <th class="text-left px-4 py-3 font-semibold text-slate-700">Dataset</th>
                <th class="text-right px-4 py-3 font-semibold text-slate-700">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($daftarSiswa)): ?>
            <tr>
                <td colspan="6" class="text-center py-12 text-slate-400">Belum ada data siswa.</td>
            </tr>
            <?php else: ?>
            <?php foreach ($daftarSiswa as $i => $s):
                $jmlDs = $datasetInfo[$s['nis']] ?? 0;
            ?>
            <tr class="border-b border-slate-100 <?= $i % 2 !== 0 ? 'bg-slate-50/50' : '' ?> hover:bg-blue-50/40 transition-colors">
                <td class="px-4 py-3 font-mono text-slate-600"><?= htmlspecialchars($s['nis']) ?></td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-3">
                        <?php if (!empty($s['foto'])): ?>
                        <img src="<?= APP_URL . '/public/' . htmlspecialchars($s['foto']) ?>"
                             class="w-8 h-8 rounded-full object-cover border border-slate-200"
                             alt="">
                        <?php else: ?>
                        <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-xs font-bold text-slate-500">
                            <?= mb_strtoupper(mb_substr($s['nama'], 0, 1)) ?>
                        </div>
                        <?php endif; ?>
                        <span class="font-medium text-slate-900"><?= htmlspecialchars($s['nama']) ?></span>
                    </div>
                </td>
                <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($s['nama_kelas']) ?></td>
                <td class="px-4 py-3">
                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium
                        <?= $s['aktif'] ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-slate-100 text-slate-500 border border-slate-200' ?>">
                        <?= $s['aktif'] ? 'Aktif' : 'Nonaktif' ?>
                    </span>
                </td>

                <!-- Kolom Dataset -->
                <td class="px-4 py-3">
                    <?php if ($jmlDs >= 10): ?>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-green-50 text-green-700 border border-green-200">
                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                            Lengkap (10)
                        </span>
                    <?php elseif ($jmlDs >= 5): ?>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                            Cukup (<?= $jmlDs ?>/10)
                        </span>
                    <?php elseif ($jmlDs > 0): ?>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                            Sebagian (<?= $jmlDs ?>/10)
                        </span>
                    <?php else: ?>
                        <span class="text-xs text-slate-400">—</span>
                    <?php endif; ?>
                </td>

                <td class="px-4 py-3 text-right">
                    <a href="<?= APP_URL ?>/siswa/dataset?id=<?= $s['id'] ?>"
                       class="inline-block px-3 py-1 text-xs font-medium
                              <?= $jmlDs >= 10 ? 'text-green-700 border border-green-200 hover:bg-green-50' : 'text-[#1E40AF] border border-blue-200 hover:bg-blue-50' ?>
                              rounded transition-colors mr-1">
                        <?= $jmlDs >= 10 ? '✓ Dataset' : 'Dataset' ?>
                    </a>
                    <a href="<?= APP_URL ?>/siswa/edit?id=<?= $s['id'] ?>"
                       class="inline-block px-3 py-1 text-xs font-medium text-slate-600 border border-slate-300 rounded hover:bg-slate-50 transition-colors mr-1">
                        Edit
                    </a>
                    <form method="POST" action="<?= APP_URL ?>/siswa/hapus" class="inline"
                          onsubmit="return confirm('Hapus siswa \"<?= htmlspecialchars($s['nama'], ENT_QUOTES) ?>\"?\nData absensi terkait juga akan terhapus.')">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <button type="submit"
                                class="px-3 py-1 text-xs font-medium text-red-600 border border-red-200 rounded hover:bg-red-50 transition-colors">
                            Hapus
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
