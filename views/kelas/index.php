<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Form tambah/edit kelas -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg border border-slate-200 p-5">
            <h2 class="text-base font-semibold text-slate-900 mb-4" id="judulForm">Tambah Kelas</h2>
            <form method="POST" action="<?= APP_URL ?>/kelas/simpan" id="formKelas" class="space-y-4">
                <input type="hidden" name="id" id="inputId" value="">

                <div>
                    <label for="nama" class="block text-sm font-medium text-slate-700 mb-1">Nama Kelas</label>
                    <input type="text" id="nama" name="nama" required placeholder="Contoh: XI TKJ 1"
                           class="w-full h-10 px-3 text-sm border border-slate-300 rounded-md
                                  focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1E40AF]">
                </div>

                <div>
                    <label for="tahun" class="block text-sm font-medium text-slate-700 mb-1">Tahun Ajaran</label>
                    <input type="text" id="tahun" name="tahun" required placeholder="Contoh: 2025/2026"
                           class="w-full h-10 px-3 text-sm border border-slate-300 rounded-md
                                  focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1E40AF]">
                </div>

                <div class="flex gap-2 pt-1">
                    <button type="submit"
                            class="flex-1 h-9 bg-[#1E40AF] hover:bg-[#1D4ED8] text-white text-sm font-semibold rounded-md transition-colors">
                        Simpan
                    </button>
                    <button type="button" onclick="resetForm()"
                            class="h-9 px-4 text-sm text-slate-600 border border-slate-300 rounded-md hover:bg-slate-50 transition-colors">
                        Reset
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel daftar kelas -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-4 py-3 font-semibold text-slate-700">Nama Kelas</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-700">Tahun Ajaran</th>
                        <th class="text-center px-4 py-3 font-semibold text-slate-700">Jumlah Siswa</th>
                        <th class="text-right px-4 py-3 font-semibold text-slate-700">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarKelas)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-12 text-slate-400">Belum ada data kelas.</td>
                    </tr>
                    <?php else: ?>
                    <?php
                    $kelasModel = new Kelas();
                    foreach ($daftarKelas as $i => $k):
                        $jumlahSiswa = $kelasModel->jumlahSiswa($k['id']);
                    ?>
                    <tr class="border-b border-slate-100 <?= $i % 2 !== 0 ? 'bg-slate-50/50' : '' ?> hover:bg-blue-50/40 transition-colors">
                        <td class="px-4 py-3 font-medium text-slate-900"><?= htmlspecialchars($k['nama']) ?></td>
                        <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($k['tahun']) ?></td>
                        <td class="px-4 py-3 text-center text-slate-600"><?= $jumlahSiswa ?></td>
                        <td class="px-4 py-3 text-right">
                            <button onclick="isiFormEdit(<?= $k['id'] ?>, '<?= htmlspecialchars($k['nama'], ENT_QUOTES) ?>', '<?= htmlspecialchars($k['tahun'], ENT_QUOTES) ?>')"
                                    class="px-3 py-1 text-xs font-medium text-slate-600 border border-slate-300 rounded hover:bg-slate-50 transition-colors mr-1">
                                Edit
                            </button>
                            <form method="POST" action="<?= APP_URL ?>/kelas/hapus" class="inline"
                                  onsubmit="return confirm('Hapus kelas <?= htmlspecialchars($k['nama'], ENT_QUOTES) ?>?')">
                                <input type="hidden" name="id" value="<?= $k['id'] ?>">
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
    </div>
</div>

<script>
function isiFormEdit(id, nama, tahun) {
    document.getElementById('inputId').value = id;
    document.getElementById('nama').value    = nama;
    document.getElementById('tahun').value   = tahun;
    document.getElementById('judulForm').textContent = 'Edit Kelas';
    document.getElementById('nama').focus();
}
function resetForm() {
    document.getElementById('formKelas').reset();
    document.getElementById('inputId').value = '';
    document.getElementById('judulForm').textContent = 'Tambah Kelas';
}
</script>
