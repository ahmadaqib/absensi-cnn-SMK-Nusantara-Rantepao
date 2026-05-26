<?php
$absenByJadwal = [];
foreach ($absensiHariIni as $absen) {
    $absenByJadwal[(int) $absen['jadwal_id']] = $absen;
}

$badgeKelas = [
    'hadir'     => 'badge-hadir',
    'terlambat' => 'badge-terlambat',
];
$labelStatus = [
    'hadir'     => 'Hadir',
    'terlambat' => 'Terlambat',
];
?>

<div class="max-w-5xl space-y-5">
    <div class="bg-white border border-slate-200 rounded-lg p-4 flex items-start justify-between gap-4">
        <div>
            <h2 class="text-sm font-semibold text-slate-900">Kehadiran Guru Hari Ini</h2>
            <p id="statusLokasiGuru" class="text-sm text-slate-500 mt-1">Mengambil lokasi GPS...</p>
        </div>
        <button type="button" id="btnAmbilLokasiGuru"
                class="h-9 px-3 text-sm font-semibold rounded-md border border-slate-200 text-slate-700 hover:bg-slate-50">
            Ambil Lokasi
        </button>
    </div>

    <div class="bg-white border border-slate-200 rounded-lg overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-200">
            <h3 class="text-sm font-semibold text-slate-700">Jadwal Mengajar</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-4 py-3 font-semibold text-slate-600 text-xs">Kelas</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600 text-xs">Mata Pelajaran</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600 text-xs">Jam</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-600 text-xs">Status</th>
                        <th class="text-right px-4 py-3 font-semibold text-slate-600 text-xs">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($jadwalHariIni)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-12 text-slate-400">Tidak ada jadwal mengajar hari ini.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($jadwalHariIni as $i => $jadwal): ?>
                    <?php
                    $absen = $absenByJadwal[(int) $jadwal['id']] ?? null;
                    $kelasAdaGps = !empty($jadwal['latitude']) && !empty($jadwal['longitude']);
                    ?>
                    <tr class="border-b border-slate-100 <?= $i % 2 !== 0 ? 'bg-slate-50/50' : '' ?>">
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-900"><?= htmlspecialchars($jadwal['nama_kelas']) ?></p>
                            <p class="text-xs text-slate-400">
                                <?= $kelasAdaGps ? 'GPS aktif, radius ' . (int) $jadwal['radius'] . ' m' : 'GPS kelas belum diatur' ?>
                            </p>
                        </td>
                        <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($jadwal['mata_pelajaran']) ?></td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">
                            <?= substr($jadwal['jam_mulai'], 0, 5) ?> - <?= substr($jadwal['jam_selesai'], 0, 5) ?>
                        </td>
                        <td class="px-4 py-3">
                            <?php if ($absen): ?>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium <?= $badgeKelas[$absen['status']] ?? 'bg-slate-50 text-slate-600 border border-slate-200' ?>">
                                <?= $labelStatus[$absen['status']] ?? htmlspecialchars($absen['status']) ?>
                            </span>
                            <p class="text-xs text-slate-400 mt-1">
                                <?= substr($absen['jam'], 0, 5) ?>
                                <?= $absen['jarak_dari_kelas'] !== null ? ' - ' . number_format((float) $absen['jarak_dari_kelas'], 1) . ' m' : '' ?>
                            </p>
                            <?php else: ?>
                            <span class="text-xs text-slate-400">Belum absen</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <?php if ($absen): ?>
                            <button type="button" disabled
                                    class="h-9 px-3 text-sm font-semibold rounded-md border border-slate-200 text-slate-400 bg-slate-50">
                                Sudah Absen
                            </button>
                            <?php else: ?>
                            <form method="POST" action="<?= APP_URL ?>/absensi-guru/simpan" class="formAbsensiGuru inline-block"
                                  data-perlu-gps="<?= $kelasAdaGps ? '1' : '0' ?>">
                                <input type="hidden" name="jadwal_id" value="<?= (int) $jadwal['id'] ?>">
                                <input type="hidden" name="latitude" class="inputLatitudeGuru">
                                <input type="hidden" name="longitude" class="inputLongitudeGuru">
                                <button type="submit"
                                        class="btnAbsenGuru h-9 px-3 text-sm font-semibold rounded-md bg-[#1E40AF] text-white hover:bg-[#1D4ED8] disabled:bg-slate-300 disabled:cursor-not-allowed"
                                        <?= $kelasAdaGps ? 'disabled' : '' ?>>
                                    Absen Sekarang
                                </button>
                            </form>
                            <?php endif; ?>
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
(() => {
    const status = document.getElementById('statusLokasiGuru');
    const btnAmbil = document.getElementById('btnAmbilLokasiGuru');
    const forms = Array.from(document.querySelectorAll('.formAbsensiGuru'));
    const adaJadwalPerluGps = forms.some((form) => form.dataset.perluGps === '1');
    let posisi = { lat: null, lon: null, akurasi: null };

    const setStatus = (pesan, kelas = 'text-slate-500') => {
        status.className = `text-sm mt-1 ${kelas}`;
        status.textContent = pesan;
    };

    const setPosisi = (pos) => {
        posisi = {
            lat: pos.coords.latitude,
            lon: pos.coords.longitude,
            akurasi: pos.coords.accuracy,
        };

        forms.forEach((form) => {
            form.querySelector('.inputLatitudeGuru').value = posisi.lat;
            form.querySelector('.inputLongitudeGuru').value = posisi.lon;
            if (form.dataset.perluGps === '1') {
                form.querySelector('.btnAbsenGuru').disabled = false;
            }
        });
        setStatus(`GPS aktif (akurasi +/-${Math.round(posisi.akurasi || 0)}m)`, 'text-green-700');
    };

    const ambilLokasi = () => {
        if (!navigator.geolocation) {
            setStatus('Browser tidak mendukung GPS.', 'text-red-700');
            return;
        }

        setStatus('Mengambil lokasi GPS...', 'text-slate-500');
        navigator.geolocation.getCurrentPosition(
            setPosisi,
            (err) => {
                const pesan = err.code === 1
                    ? 'Izin GPS ditolak. Aktifkan izin lokasi untuk absen.'
                    : 'GPS belum tersedia. Coba ambil lokasi ulang.';
                setStatus(pesan, 'text-red-700');
            },
            { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
        );
    };

    forms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.perluGps !== '1') {
                return;
            }

            if (!form.querySelector('.btnAbsenGuru').disabled && posisi.lat !== null && posisi.lon !== null) {
                return;
            }

            event.preventDefault();
            ambilLokasi();
        });
    });

    btnAmbil.addEventListener('click', ambilLokasi);
    if (adaJadwalPerluGps) {
        ambilLokasi();
    } else {
        setStatus('GPS kelas belum diatur pada jadwal hari ini, absensi bisa dilakukan tanpa validasi lokasi.');
    }
})();
</script>
