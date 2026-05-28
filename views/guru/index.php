<?php
$datasetInfo = [];
foreach ($daftarGuru as $guru) {
    $dir = BASE_PATH . '/python/dataset/' . $guru['username'] . '/';
    $datasetInfo[(int) $guru['id']] = is_dir($dir) ? count(glob($dir . '*.jpg')) : 0;
}
?>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <section class="xl:col-span-1">
        <div class="bg-white rounded-lg border border-slate-200 p-5">
            <h2 class="text-base font-semibold text-slate-900 mb-4" id="judulForm">Tambah Guru</h2>
            <form method="POST" action="<?= APP_URL ?>/guru/simpan" id="formGuru" class="space-y-4">
                <input type="hidden" name="id" id="inputId" value="">

                <div>
                    <label for="nama" class="block text-sm font-medium text-slate-700 mb-1">Nama Lengkap</label>
                    <input type="text" id="nama" name="nama" required
                           class="w-full h-10 px-3 text-sm border border-slate-300 rounded-md
                                  focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1E40AF]">
                </div>

                <div>
                    <label for="username" class="block text-sm font-medium text-slate-700 mb-1">Username</label>
                    <input type="text" id="username" name="username" required autocomplete="off"
                           class="w-full h-10 px-3 text-sm border border-slate-300 rounded-md font-mono
                                  focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1E40AF]">
                    <p class="text-xs text-slate-400 mt-1">Huruf, angka, dan underscore. Username juga menjadi label dataset wajah.</p>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="new-password"
                           class="w-full h-10 px-3 text-sm border border-slate-300 rounded-md
                                  focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1E40AF]">
                    <p class="text-xs text-slate-400 mt-1" id="bantuanPassword">Wajib saat tambah guru.</p>
                </div>

                <div class="flex gap-2 pt-1">
                    <button type="submit" id="btnSubmit"
                            class="flex-1 h-10 bg-[#1E40AF] hover:bg-[#1D4ED8] text-white text-sm font-semibold rounded-md transition-colors">
                        Tambah Guru
                    </button>
                    <button type="button" onclick="resetFormGuru()"
                            class="h-10 px-4 text-sm font-medium text-slate-600 border border-slate-300 rounded-md hover:bg-slate-50 transition-colors">
                        Reset
                    </button>
                </div>
            </form>
        </div>
    </section>

    <section class="xl:col-span-2 bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-800">Daftar Guru</h3>
            <span class="text-xs text-slate-400"><?= count($daftarGuru) ?> guru</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-4 py-3 font-semibold text-slate-700">Guru</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-700">Username</th>
                        <th class="text-center px-4 py-3 font-semibold text-slate-700">Jadwal</th>
                        <th class="text-center px-4 py-3 font-semibold text-slate-700">Absensi</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-700">Dataset</th>
                        <th class="text-right px-4 py-3 font-semibold text-slate-700">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarGuru)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-12 text-slate-400">Belum ada data guru.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($daftarGuru as $i => $guru): ?>
                    <?php
                    $jumlahDataset = $datasetInfo[(int) $guru['id']] ?? 0;
                    $payload = [
                        'id'       => (int) $guru['id'],
                        'nama'     => $guru['nama'],
                        'username' => $guru['username'],
                    ];
                    ?>
                    <tr class="border-b border-slate-100 <?= $i % 2 !== 0 ? 'bg-slate-50/50' : '' ?> hover:bg-blue-50/40 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-blue-50 border border-blue-100 flex items-center justify-center text-xs font-bold text-[#1E40AF]">
                                    <?= mb_strtoupper(mb_substr($guru['nama'], 0, 1)) ?>
                                </div>
                                <div>
                                    <p class="font-medium text-slate-900"><?= htmlspecialchars($guru['nama']) ?></p>
                                    <p class="text-xs text-slate-400">Dibuat <?= htmlspecialchars(substr($guru['dibuat_pada'] ?? '', 0, 10)) ?></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 font-mono text-slate-600"><?= htmlspecialchars($guru['username']) ?></td>
                        <td class="px-4 py-3 text-center text-slate-600"><?= (int) $guru['jumlah_jadwal'] ?></td>
                        <td class="px-4 py-3 text-center text-slate-600"><?= (int) $guru['jumlah_absensi'] ?></td>
                        <td class="px-4 py-3">
                            <?php if ($jumlahDataset >= 10): ?>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-green-50 text-green-700 border border-green-200">
                                    Lengkap (10)
                                </span>
                            <?php elseif ($jumlahDataset > 0): ?>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                    <?= $jumlahDataset ?>/10
                                </span>
                            <?php else: ?>
                                <span class="text-xs text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button type="button"
                                    onclick="isiFormEditGuru(<?= htmlspecialchars(json_encode($payload), ENT_QUOTES, 'UTF-8') ?>)"
                                    class="px-3 py-1 text-xs font-medium text-slate-600 border border-slate-300 rounded hover:bg-slate-50 transition-colors mr-1">
                                Edit
                            </button>
                            <form method="POST" action="<?= APP_URL ?>/guru/hapus" class="inline"
                                  onsubmit="return confirm('Hapus guru <?= htmlspecialchars($guru['nama'], ENT_QUOTES) ?>?\nJadwal, absensi, antrian, notifikasi, dan dataset wajah terkait juga akan dihapus.')">
                                <input type="hidden" name="id" value="<?= (int) $guru['id'] ?>">
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
    </section>
</div>

<script>
function isiFormEditGuru(guru) {
    document.getElementById('inputId').value = guru.id;
    document.getElementById('nama').value = guru.nama;
    document.getElementById('username').value = guru.username;
    document.getElementById('password').value = '';
    document.getElementById('password').required = false;
    document.getElementById('judulForm').textContent = 'Edit Guru';
    document.getElementById('btnSubmit').textContent = 'Simpan Perubahan';
    document.getElementById('bantuanPassword').textContent = 'Kosongkan jika password tidak diubah.';
    document.getElementById('nama').focus();
}

function resetFormGuru() {
    document.getElementById('formGuru').reset();
    document.getElementById('inputId').value = '';
    document.getElementById('password').required = true;
    document.getElementById('judulForm').textContent = 'Tambah Guru';
    document.getElementById('btnSubmit').textContent = 'Tambah Guru';
    document.getElementById('bantuanPassword').textContent = 'Wajib saat tambah guru.';
}
</script>
