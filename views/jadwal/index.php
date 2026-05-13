<?php
$hariUrut = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];

// Kelompokkan jadwal per hari
$jadwalPerHari = [];
foreach ($daftarJadwal as $j) {
    $jadwalPerHari[$j['hari']][] = $j;
}
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Form tambah/edit jadwal -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-lg border border-slate-200 p-5">
            <h2 class="text-base font-semibold text-slate-900 mb-4" id="judulForm">Tambah Jadwal</h2>
            <form method="POST" action="<?= APP_URL ?>/jadwal/simpan" id="formJadwal" class="space-y-4">
                <input type="hidden" name="id" id="inputId" value="">

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Kelas</label>
                    <select name="kelas_id" id="kelasId" required
                            class="w-full h-10 px-3 text-sm border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1E40AF]">
                        <option value="">— Pilih kelas —</option>
                        <?php foreach ($daftarKelas as $k): ?>
                        <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nama']) ?> (<?= $k['tahun'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Guru</label>
                    <select name="guru_id" id="guruId" required
                            class="w-full h-10 px-3 text-sm border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1E40AF]">
                        <option value="">— Pilih guru —</option>
                        <?php foreach ($daftarGuru as $g): ?>
                        <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Mata Pelajaran</label>
                    <input type="text" name="mata_pelajaran" id="mapel" required
                           class="w-full h-10 px-3 text-sm border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1E40AF]">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Hari</label>
                    <select name="hari" id="hari" required
                            class="w-full h-10 px-3 text-sm border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1E40AF]">
                        <option value="">— Pilih hari —</option>
                        <?php foreach ($hariUrut as $h): ?>
                        <option value="<?= $h ?>"><?= $h ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Jam Mulai</label>
                        <input type="time" name="jam_mulai" id="jamMulai" required
                               class="w-full h-10 px-3 text-sm border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1E40AF]">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Jam Selesai</label>
                        <input type="time" name="jam_selesai" id="jamSelesai" required
                               class="w-full h-10 px-3 text-sm border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1E40AF]">
                    </div>
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

    <!-- Tabel jadwal per hari -->
    <div class="lg:col-span-2 space-y-4">
        <?php foreach ($hariUrut as $hari): ?>
        <?php if (!empty($jadwalPerHari[$hari])): ?>
        <div class="bg-white rounded-lg border border-slate-200 overflow-hidden">
            <div class="px-4 py-2.5 bg-slate-50 border-b border-slate-200">
                <h3 class="text-sm font-semibold text-slate-700"><?= $hari ?></h3>
            </div>
            <table class="w-full text-sm">
                <tbody>
                    <?php foreach ($jadwalPerHari[$hari] as $i => $j): ?>
                    <tr class="border-b border-slate-100 <?= $i % 2 !== 0 ? 'bg-slate-50/50' : '' ?> hover:bg-blue-50/40 transition-colors">
                        <td class="px-4 py-2.5 font-mono text-slate-500 text-xs w-28">
                            <?= substr($j['jam_mulai'], 0, 5) ?>–<?= substr($j['jam_selesai'], 0, 5) ?>
                        </td>
                        <td class="px-4 py-2.5 font-medium text-slate-900"><?= htmlspecialchars($j['mata_pelajaran']) ?></td>
                        <td class="px-4 py-2.5 text-slate-500"><?= htmlspecialchars($j['nama_kelas']) ?></td>
                        <td class="px-4 py-2.5 text-slate-500"><?= htmlspecialchars($j['nama_guru']) ?></td>
                        <td class="px-4 py-2.5 text-right">
                            <button onclick="isiFormEdit(<?= htmlspecialchars(json_encode($j)) ?>)"
                                    class="px-2.5 py-1 text-xs text-slate-600 border border-slate-300 rounded hover:bg-slate-50 transition-colors mr-1">
                                Edit
                            </button>
                            <form method="POST" action="<?= APP_URL ?>/jadwal/hapus" class="inline"
                                  onsubmit="return confirm('Hapus jadwal ini?')">
                                <input type="hidden" name="id" value="<?= $j['id'] ?>">
                                <button type="submit"
                                        class="px-2.5 py-1 text-xs text-red-600 border border-red-200 rounded hover:bg-red-50 transition-colors">
                                    Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>

        <?php if (empty($daftarJadwal)): ?>
        <div class="bg-white rounded-lg border border-slate-200 py-12 text-center text-slate-400 text-sm">
            Belum ada jadwal. Tambahkan jadwal menggunakan form di sebelah kiri.
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function isiFormEdit(j) {
    document.getElementById('inputId').value  = j.id;
    document.getElementById('kelasId').value  = j.kelas_id;
    document.getElementById('guruId').value   = j.guru_id;
    document.getElementById('mapel').value    = j.mata_pelajaran;
    document.getElementById('hari').value     = j.hari;
    document.getElementById('jamMulai').value = j.jam_mulai;
    document.getElementById('jamSelesai').value = j.jam_selesai;
    document.getElementById('judulForm').textContent = 'Edit Jadwal';
    document.getElementById('mapel').focus();
}
function resetForm() {
    document.getElementById('formJadwal').reset();
    document.getElementById('inputId').value = '';
    document.getElementById('judulForm').textContent = 'Tambah Jadwal';
}
</script>
