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
                    $labelGps = ($jadwal['sumber_koordinat'] ?? 'kelas') === 'sekolah' ? 'GPS sekolah' : 'GPS kelas';
                    ?>
                    <tr class="border-b border-slate-100 <?= $i % 2 !== 0 ? 'bg-slate-50/50' : '' ?>">
                        <td class="px-4 py-3">
                            <p class="font-medium text-slate-900"><?= htmlspecialchars($jadwal['nama_kelas']) ?></p>
                            <p class="text-xs text-slate-400">
                                <?= $kelasAdaGps ? $labelGps . ' aktif, radius ' . (int) $jadwal['radius'] . ' m' : $labelGps . ' belum diatur' ?>
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

<!-- Modal Kamera Face Recognition Guru -->
<div id="modalKameraGuru" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-md mx-4 p-5 flex flex-col">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="font-bold text-slate-900 text-sm">Verifikasi Wajah (CNN)</h3>
            <button type="button" id="btnCloseModalGuru" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <div class="mt-4 relative bg-slate-900 rounded-lg overflow-hidden" style="aspect-ratio:4/3">
            <video id="videoGuru" autoplay playsinline muted class="w-full h-full object-cover" style="transform:scaleX(-1)"></video>
            <!-- Guide overlay -->
            <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
                <div class="w-32 h-40 rounded-xl" style="border:2px dashed rgba(255,255,255,0.35)"></div>
            </div>
        </div>

        <canvas id="canvasGuru" class="hidden"></canvas>
        <p id="statusCnnGuru" class="text-xs text-center text-slate-500 mt-3">Menyalakan kamera...</p>

        <div class="mt-4 flex gap-2 justify-end">
            <button type="button" id="btnCancelCaptureGuru" class="px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 rounded-md hover:bg-slate-50 transition-colors">
                Batal
            </button>
            <button type="button" id="btnCaptureGuru" disabled class="px-4 py-2 text-sm font-semibold text-white bg-[#1E40AF] hover:bg-[#1D4ED8] disabled:bg-slate-300 rounded-md transition-colors">
                Ambil Foto & Absen
            </button>
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

    // Modal elements
    const modal = document.getElementById('modalKameraGuru');
    const video = document.getElementById('videoGuru');
    const canvas = document.getElementById('canvasGuru');
    const btnCapture = document.getElementById('btnCaptureGuru');
    const btnClose = document.getElementById('btnCloseModalGuru');
    const btnCancel = document.getElementById('btnCancelCaptureGuru');
    const statusCnn = document.getElementById('statusCnnGuru');

    let stream = null;
    let currentForm = null;

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
            form.querySelector('.btnAbsenGuru').disabled = false;
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

    const bukaModalKamera = () => {
        modal.classList.remove('hidden');
        statusCnn.textContent = 'Menyalakan kamera...';
        statusCnn.className = 'text-xs text-center text-slate-500 mt-3';
        btnCapture.disabled = true;
        btnCapture.textContent = 'Ambil Foto & Absen';

        navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480 } })
            .then(s => {
                stream = s;
                video.srcObject = s;
                video.onloadedmetadata = () => {
                    btnCapture.disabled = false;
                    statusCnn.textContent = 'Kamera siap. Posisikan wajah di dalam bingkai lalu klik "Ambil Foto & Absen".';
                };
            })
            .catch(() => {
                statusCnn.textContent = 'Gagal mengakses kamera. Harap izinkan akses kamera di browser Anda.';
                statusCnn.className = 'text-xs text-center text-red-600 mt-3';
            });
    };

    const tutupModalKamera = () => {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            stream = null;
        }
        video.srcObject = null;
        modal.classList.add('hidden');
        currentForm = null;
    };

    forms.forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();

            if (form.dataset.perluGps === '1' && (posisi.lat === null || posisi.lon === null)) {
                ambilLokasi();
                return;
            }

            currentForm = form;
            bukaModalKamera();
        });
    });

    btnClose.addEventListener('click', tutupModalKamera);
    btnCancel.addEventListener('click', tutupModalKamera);

    btnCapture.addEventListener('click', () => {
        if (!currentForm) return;

        btnCapture.disabled = true;
        btnCapture.textContent = 'Memproses...';
        statusCnn.textContent = 'Mendeteksi wajah & mencocokkan identitas...';
        statusCnn.className = 'text-xs text-center text-blue-600 mt-3';

        canvas.width = video.videoWidth || 640;
        canvas.height = video.videoHeight || 480;
        canvas.getContext('2d').drawImage(video, 0, 0);
        const base64 = canvas.toDataURL('image/jpeg', 0.85);

        const formData = new FormData();
        formData.append('jadwal_id', currentForm.querySelector('[name=jadwal_id]').value);
        formData.append('latitude', currentForm.querySelector('.inputLatitudeGuru').value);
        formData.append('longitude', currentForm.querySelector('.inputLongitudeGuru').value);
        formData.append('gambar', base64);

        fetch('<?= APP_URL ?>/absensi-guru/simpan', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'sukses') {
                statusCnn.textContent = data.pesan || 'Kehadiran berhasil dicatat!';
                statusCnn.className = 'text-xs text-center text-green-600 mt-3';
                setTimeout(() => {
                    tutupModalKamera();
                    window.location.reload();
                }, 1500);
            } else {
                statusCnn.textContent = data.pesan || 'Verifikasi gagal.';
                statusCnn.className = 'text-xs text-center text-red-600 mt-3';
                btnCapture.disabled = false;
                btnCapture.textContent = 'Ambil Foto & Absen';
            }
        })
        .catch(() => {
            statusCnn.textContent = 'Error koneksi. Harap ulangi.';
            statusCnn.className = 'text-xs text-center text-red-600 mt-3';
            btnCapture.disabled = false;
            btnCapture.textContent = 'Ambil Foto & Absen';
        });
    });

    btnAmbil.addEventListener('click', ambilLokasi);
    if (adaJadwalPerluGps) {
        ambilLokasi();
    } else {
        setStatus('GPS belum diatur pada jadwal hari ini, absensi bisa dilakukan tanpa validasi lokasi.');
    }
})();
</script>
