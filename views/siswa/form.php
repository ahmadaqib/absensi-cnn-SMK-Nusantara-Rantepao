<?php $editMode = !empty($siswa['id']); ?>

<div class="max-w-lg">
    <form method="POST"
          action="<?= APP_URL . ($editMode ? '/siswa/edit' : '/siswa/tambah') ?>"
          enctype="multipart/form-data"
          class="bg-white rounded-lg border border-slate-200 p-6 space-y-5">

        <?php if ($editMode): ?>
        <input type="hidden" name="id" value="<?= $siswa['id'] ?>">
        <?php endif; ?>

        <!-- Nama -->
        <div>
            <label for="nama" class="block text-sm font-medium text-slate-700 mb-1">Nama Lengkap</label>
            <input type="text" id="nama" name="nama" required
                   value="<?= htmlspecialchars($siswa['nama'] ?? '') ?>"
                   class="w-full h-10 px-3 text-sm border border-slate-300 rounded-md
                          focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1E40AF]">
        </div>

        <!-- NIS -->
        <div>
            <label for="nis" class="block text-sm font-medium text-slate-700 mb-1">NIS</label>
            <input type="text" id="nis" name="nis" required
                   value="<?= htmlspecialchars($siswa['nis'] ?? '') ?>"
                   class="w-full h-10 px-3 text-sm border border-slate-300 rounded-md font-mono
                          focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1E40AF]">
        </div>

        <!-- Kelas -->
        <div>
            <label for="kelas_id" class="block text-sm font-medium text-slate-700 mb-1">Kelas</label>
            <select id="kelas_id" name="kelas_id" required
                    class="w-full h-10 px-3 text-sm border border-slate-300 rounded-md
                           focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1E40AF]">
                <option value="">— Pilih kelas —</option>
                <?php foreach ($daftarKelas as $k): ?>
                <option value="<?= $k['id'] ?>"
                    <?= (($siswa['kelas_id'] ?? 0) == $k['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($k['nama']) ?> (<?= htmlspecialchars($k['tahun']) ?>)
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Foto profil -->
        <div>
            <label for="foto" class="block text-sm font-medium text-slate-700 mb-1">
                Foto Profil <span class="text-slate-400 font-normal">(opsional, maks 5 MB)</span>
            </label>
            <?php if (!empty($siswa['foto'])): ?>
            <div class="mb-2">
                <img src="<?= APP_URL . '/public/' . htmlspecialchars($siswa['foto']) ?>"
                     class="w-16 h-16 rounded-full object-cover border border-slate-200" alt="Foto saat ini">
            </div>
            <?php endif; ?>
            <input type="file" id="foto" name="foto" accept="image/jpeg,image/png"
                   class="w-full text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3
                          file:rounded file:border file:border-slate-300 file:text-sm
                          file:bg-white file:text-slate-700 hover:file:bg-slate-50">
        </div>

        <!-- Status (hanya saat edit) -->
        <?php if ($editMode): ?>
        <div>
            <label for="aktif" class="block text-sm font-medium text-slate-700 mb-1">Status</label>
            <select id="aktif" name="aktif"
                    class="w-full h-10 px-3 text-sm border border-slate-300 rounded-md
                           focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1E40AF]">
                <option value="1" <?= ($siswa['aktif'] ?? 1) == 1 ? 'selected' : '' ?>>Aktif</option>
                <option value="0" <?= ($siswa['aktif'] ?? 1) == 0 ? 'selected' : '' ?>>Nonaktif</option>
            </select>
        </div>
        <?php endif; ?>

        <!-- Tombol -->
        <div class="flex gap-3 pt-2">
            <button type="submit"
                    class="h-10 px-6 bg-[#1E40AF] hover:bg-[#1D4ED8] text-white text-sm font-semibold rounded-md transition-colors">
                <?= $editMode ? 'Simpan Perubahan' : 'Tambah Siswa' ?>
            </button>
            <a href="<?= APP_URL ?>/siswa"
               class="h-10 px-6 flex items-center text-sm font-medium text-slate-600 border border-slate-300 rounded-md hover:bg-slate-50 transition-colors">
                Batal
            </a>
        </div>
    </form>
</div>
