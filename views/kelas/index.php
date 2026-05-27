<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
    #mapKelas { height: clamp(380px, 56vh, 640px); min-height: 380px; }
    .leaflet-container { font-family: Inter, sans-serif; }
    .kelas-marker {
        position: relative;
        width: 30px;
        height: 30px;
        border-radius: 9999px 9999px 9999px 4px;
        background: #1E40AF;
        border: 3px solid #ffffff;
        box-shadow: 0 10px 24px rgba(15, 23, 42, .28);
        transform: rotate(-45deg);
    }
    .kelas-marker::after {
        content: '';
        position: absolute;
        width: 8px;
        height: 8px;
        border-radius: 9999px;
        background: #ffffff;
        left: 8px;
        top: 8px;
    }
    .map-pin-label {
        background: #ffffff;
        border: 1px solid #BFDBFE;
        border-radius: 6px;
        color: #1E40AF;
        font-size: 11px;
        font-weight: 700;
        padding: 3px 7px;
        box-shadow: 0 6px 18px rgba(15, 23, 42, .12);
    }
    .map-pin-label::before { display: none; }
    @media (max-width: 640px) {
        #mapKelas { height: 360px; min-height: 360px; }
    }
</style>

<?php
$latSekolah = $koordinatSekolah['latitude'] ?? '';
$lngSekolah = $koordinatSekolah['longitude'] ?? '';
$radiusSekolah = (int) ($koordinatSekolah['radius'] ?? RADIUS_MAKSIMAL);
?>

<div class="space-y-6">
    <section class="bg-white rounded-lg border border-slate-200 p-5 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <p class="text-xs font-semibold text-[#1E40AF] uppercase tracking-wide">Koordinat Sekolah</p>
            <h2 class="text-lg font-semibold text-slate-900 mt-1">
                <?= $sekolahAdaGps ? 'Titik sekolah sudah aktif' : 'Titik sekolah belum diatur' ?>
            </h2>
            <p class="text-sm text-slate-500 mt-1">
                Kelas bisa mengikuti koordinat sekolah, atau memakai titik koordinat sendiri jika ruang kelas perlu radius berbeda.
            </p>
        </div>
        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="<?= $sekolahAdaGps ? 'bg-green-50 text-green-700 border-green-200' : 'bg-amber-50 text-amber-700 border-amber-200' ?> border rounded-md px-3 py-2 text-sm">
                <?= $sekolahAdaGps
                    ? htmlspecialchars($latSekolah) . ', ' . htmlspecialchars($lngSekolah) . ' · ' . $radiusSekolah . 'm'
                    : 'Isi di menu Pengaturan sebelum memilih mode sekolah.' ?>
            </div>
            <a href="<?= APP_URL ?>/pengaturan"
               class="h-10 inline-flex items-center justify-center px-4 text-sm font-semibold text-[#1E40AF] border border-blue-200 rounded-md hover:bg-blue-50 transition-colors">
                Atur Titik Sekolah
            </a>
        </div>
    </section>

    <section class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <form method="POST" action="<?= APP_URL ?>/kelas/simpan" id="formKelas">
            <input type="hidden" name="id" id="inputId" value="">

            <div class="grid grid-cols-1 xl:grid-cols-12">
                <div class="xl:col-span-4 p-5 border-b xl:border-b-0 xl:border-r border-slate-200 space-y-5">
                    <div>
                        <p class="text-xs font-semibold text-[#1E40AF] uppercase tracking-wide">Data Kelas</p>
                        <h2 class="text-lg font-semibold text-slate-900 mt-1" id="judulForm">Tambah Kelas</h2>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-1 gap-4">
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
                    </div>

                    <div class="border-t border-slate-100 pt-5 space-y-4">
                        <div>
                            <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Geofencing</p>
                            <p id="infoMapKelas" class="text-xs text-slate-400 mt-1">
                                Pilih sumber koordinat untuk validasi lokasi absensi kelas ini.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 gap-2">
                            <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-3 cursor-pointer hover:bg-slate-50">
                                <input type="radio" name="sumber_koordinat" value="sekolah" class="mt-1" checked>
                                <span>
                                    <span class="block text-sm font-semibold text-slate-800">Ikuti koordinat sekolah</span>
                                    <span class="block text-xs text-slate-500 mt-0.5">
                                        Gunakan titik dan radius dari menu Pengaturan.
                                    </span>
                                </span>
                            </label>
                            <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-3 cursor-pointer hover:bg-slate-50">
                                <input type="radio" name="sumber_koordinat" value="kelas" class="mt-1">
                                <span>
                                    <span class="block text-sm font-semibold text-slate-800">Tentukan koordinat sendiri</span>
                                    <span class="block text-xs text-slate-500 mt-0.5">
                                        Pakai titik khusus untuk ruang kelas ini.
                                    </span>
                                </span>
                            </label>
                        </div>

                        <div id="panelKoordinatKelas" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-1 gap-3">
                            <div>
                                <label for="latitude" class="block text-xs font-medium text-slate-600 mb-1">Latitude</label>
                                <input type="text" id="latitude" name="latitude" placeholder="-3.948250"
                                       class="w-full h-9 px-2 text-sm font-mono border border-slate-300 rounded-md
                                              focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1E40AF]">
                            </div>
                            <div>
                                <label for="longitude" class="block text-xs font-medium text-slate-600 mb-1">Longitude</label>
                                <input type="text" id="longitude" name="longitude" placeholder="119.899870"
                                       class="w-full h-9 px-2 text-sm font-mono border border-slate-300 rounded-md
                                              focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1E40AF]">
                            </div>
                        </div>

                        <div class="rounded-lg border border-slate-200 p-3 bg-slate-50">
                            <div class="flex items-center justify-between gap-3 mb-2">
                                <label for="radius" class="text-xs font-medium text-slate-600">Radius Geofencing</label>
                                <span id="radiusBadge" class="px-2 py-1 rounded-full bg-blue-50 text-[#1E40AF] text-xs font-semibold">50 meter</span>
                            </div>
                            <div class="grid grid-cols-[1fr_88px] gap-3 items-center">
                                <input type="range" id="radiusRange" min="10" max="500" step="5" value="50"
                                       class="w-full accent-[#1E40AF]">
                                <input type="number" id="radius" name="radius" value="50" min="10" max="500"
                                       class="w-full h-9 px-2 text-sm border border-slate-300 rounded-md
                                              focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1E40AF]">
                            </div>
                            <div class="flex justify-between mt-1 text-[10px] text-slate-400">
                                <span>10 m</span>
                                <span>500 m</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-1 gap-2">
                            <button type="button" id="btnLokasiSaatIni"
                                    class="h-10 px-3 text-sm font-semibold text-[#1E40AF] border border-blue-200 rounded-md hover:bg-blue-50 transition-colors">
                                Gunakan Lokasi Saat Ini
                            </button>
                            <button type="button" id="btnResetKoordinat"
                                    class="h-10 px-3 text-sm font-semibold text-slate-600 border border-slate-300 rounded-md hover:bg-slate-50 transition-colors">
                                Kosongkan GPS
                            </button>
                        </div>
                        </div>
                    </div>

                    <div class="flex gap-2 pt-1">
                        <button type="submit"
                                class="flex-1 h-10 bg-[#1E40AF] hover:bg-[#1D4ED8] text-white text-sm font-semibold rounded-md transition-colors">
                            Simpan Kelas
                        </button>
                        <button type="button" onclick="resetForm()"
                                class="h-10 px-4 text-sm font-medium text-slate-600 border border-slate-300 rounded-md hover:bg-slate-50 transition-colors">
                            Reset
                        </button>
                    </div>
                </div>

                <div class="xl:col-span-8 p-5 space-y-3">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Peta Radius Kelas</h3>
                            <p id="teksBantuanPeta" class="text-sm text-slate-500">Pin biru adalah titik ruang kelas. Area biru adalah radius absensi.</p>
                        </div>
                        <div id="statusGpsKelas" class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-500">
                            <span class="w-2 h-2 rounded-full bg-slate-300"></span>
                            GPS belum diatur
                        </div>
                    </div>

                    <div class="relative rounded-lg border border-slate-200 overflow-hidden bg-slate-100">
                        <div id="mapKelas" class="w-full"></div>
                        <div class="absolute right-3 top-3 z-[500] max-w-[calc(100%-1.5rem)] rounded-md border border-blue-100 bg-white/95 px-3 py-2 shadow-sm pointer-events-none">
                            <p class="text-xs font-semibold text-slate-900">Klik peta untuk memilih lokasi</p>
                            <p class="text-[11px] text-slate-500 mt-0.5">Marker bisa digeser setelah dipasang.</p>
                        </div>
                        <div class="absolute left-3 right-3 bottom-3 z-[500] grid grid-cols-1 sm:grid-cols-3 gap-2 pointer-events-none">
                            <div class="bg-white/95 border border-slate-200 rounded-md px-3 py-2 shadow-sm">
                                <p class="text-[10px] font-semibold text-slate-400 uppercase">Latitude</p>
                                <p id="previewLat" class="text-xs font-mono text-slate-700 truncate">-</p>
                            </div>
                            <div class="bg-white/95 border border-slate-200 rounded-md px-3 py-2 shadow-sm">
                                <p class="text-[10px] font-semibold text-slate-400 uppercase">Longitude</p>
                                <p id="previewLng" class="text-xs font-mono text-slate-700 truncate">-</p>
                            </div>
                            <div class="bg-white/95 border border-blue-100 rounded-md px-3 py-2 shadow-sm">
                                <p class="text-[10px] font-semibold text-blue-400 uppercase">Radius</p>
                                <p id="previewRadius" class="text-xs font-semibold text-[#1E40AF]">50 meter</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>

    <!-- Tabel daftar kelas -->
    <section class="bg-white rounded-lg border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-800">Daftar Kelas</h3>
            <span class="text-xs text-slate-400"><?= count($daftarKelas) ?> kelas</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="text-left px-4 py-3 font-semibold text-slate-700">Nama Kelas</th>
                        <th class="text-left px-4 py-3 font-semibold text-slate-700">Tahun Ajaran</th>
                        <th class="text-center px-4 py-3 font-semibold text-slate-700">Siswa</th>
                        <th class="text-center px-4 py-3 font-semibold text-slate-700">GPS</th>
                        <th class="text-right px-4 py-3 font-semibold text-slate-700">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daftarKelas)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-12 text-slate-400">Belum ada data kelas.</td>
                    </tr>
                    <?php else: ?>
                    <?php
                    $kelasModel = new Kelas();
                    foreach ($daftarKelas as $i => $k):
                        $jumlahSiswa = $kelasModel->jumlahSiswa($k['id']);
                        $sumberKoordinat = $k['sumber_koordinat'] ?? ((!empty($k['latitude']) && !empty($k['longitude'])) ? 'kelas' : 'sekolah');
                        $koordinatEfektif = $kelasModel->ambilKoordinat((int) $k['id']);
                        $adaGps = $koordinatEfektif && !empty($koordinatEfektif['latitude']) && !empty($koordinatEfektif['longitude']);
                    ?>
                    <tr class="border-b border-slate-100 <?= $i % 2 !== 0 ? 'bg-slate-50/50' : '' ?> hover:bg-blue-50/40 transition-colors">
                        <td class="px-4 py-3 font-medium text-slate-900"><?= htmlspecialchars($k['nama']) ?></td>
                        <td class="px-4 py-3 text-slate-600"><?= htmlspecialchars($k['tahun']) ?></td>
                        <td class="px-4 py-3 text-center text-slate-600"><?= $jumlahSiswa ?></td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($adaGps): ?>
                            <span class="inline-flex items-center gap-1 text-xs text-green-700">
                                <span class="w-2 h-2 rounded-full bg-green-400"></span>
                                <?= $sumberKoordinat === 'sekolah' ? 'Sekolah' : 'Kelas' ?> · <?= number_format((float)($koordinatEfektif['radius'] ?? RADIUS_MAKSIMAL), 0) ?>m
                            </span>
                            <?php else: ?>
                            <span class="text-xs text-amber-600">
                                <?= $sumberKoordinat === 'sekolah' ? 'Sekolah belum diatur' : 'GPS belum diatur' ?>
                            </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button onclick="isiFormEdit(
                                        <?= $k['id'] ?>,
                                        '<?= htmlspecialchars($k['nama'], ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($k['tahun'], ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($sumberKoordinat, ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($k['latitude'] ?? '', ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($k['longitude'] ?? '', ENT_QUOTES) ?>',
                                        '<?= (int)($k['radius'] ?? 50) ?>'
                                    )"
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
    </section>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const DEFAULT_LAT = -3.948250;
const DEFAULT_LNG = 119.899870;
const SEKOLAH_LAT = <?= $latSekolah !== '' ? json_encode((float) $latSekolah) : 'null' ?>;
const SEKOLAH_LNG = <?= $lngSekolah !== '' ? json_encode((float) $lngSekolah) : 'null' ?>;
const SEKOLAH_RADIUS = <?= json_encode($radiusSekolah) ?>;

let mapKelas;
let markerKelas = null;
let circleRadius = null;
let labelRadius = null;
let markerIconKelas = null;

function sumberKoordinat() {
    const checked = document.querySelector('input[name="sumber_koordinat"]:checked');
    return checked ? checked.value : 'sekolah';
}

function setSumberKoordinat(sumber) {
    const radio = document.querySelector(`input[name="sumber_koordinat"][value="${sumber}"]`);
    if (radio) radio.checked = true;
    updateModeKoordinat();
}

function nilaiKoordinat() {
    if (sumberKoordinat() === 'sekolah') {
        return {
            lat: Number.isFinite(SEKOLAH_LAT) ? SEKOLAH_LAT : null,
            lng: Number.isFinite(SEKOLAH_LNG) ? SEKOLAH_LNG : null,
            radius: clampRadius(SEKOLAH_RADIUS),
        };
    }

    const lat = parseFloat(document.getElementById('latitude').value);
    const lng = parseFloat(document.getElementById('longitude').value);
    const radius = clampRadius(document.getElementById('radius').value || '50');
    return {
        lat: Number.isFinite(lat) ? lat : null,
        lng: Number.isFinite(lng) ? lng : null,
        radius,
    };
}

function clampRadius(value) {
    const angka = parseInt(value, 10);
    if (!Number.isFinite(angka)) return 50;
    return Math.min(500, Math.max(10, angka));
}

function setRadiusValue(value) {
    const radius = clampRadius(value);
    document.getElementById('radius').value = radius;
    document.getElementById('radiusRange').value = radius;
    return radius;
}

function setKoordinat(lat, lng, radius = null, zoom = true) {
    document.getElementById('latitude').value = Number(lat).toFixed(8);
    document.getElementById('longitude').value = Number(lng).toFixed(8);
    if (radius !== null) document.getElementById('radius').value = radius;
    if (radius !== null) setRadiusValue(radius);
    updateMapKelas(zoom);
}

function updateMapKelas(zoom = false) {
    if (!mapKelas) return;
    const nilai = nilaiKoordinat();
    updatePreviewKoordinat(nilai);

    if (nilai.lat === null || nilai.lng === null) {
        if (markerKelas) markerKelas.remove();
        if (circleRadius) circleRadius.remove();
        if (labelRadius) labelRadius.remove();
        markerKelas = null;
        circleRadius = null;
        labelRadius = null;
        return;
    }

    const posisi = [nilai.lat, nilai.lng];
    if (!markerKelas) {
        markerKelas = L.marker(posisi, { draggable: true, icon: markerIconKelas }).addTo(mapKelas);
        markerKelas.on('dragend', () => {
            const pos = markerKelas.getLatLng();
            if (sumberKoordinat() === 'sekolah') setSumberKoordinat('kelas');
            setKoordinat(pos.lat, pos.lng, null, false);
        });
    } else {
        markerKelas.setLatLng(posisi);
    }

    if (!circleRadius) {
        circleRadius = L.circle(posisi, {
            radius: nilai.radius,
            color: '#1E40AF',
            weight: 2,
            fillColor: '#60A5FA',
            fillOpacity: 0.18,
        }).addTo(mapKelas);
    } else {
        circleRadius.setLatLng(posisi);
        circleRadius.setRadius(nilai.radius);
    }

    if (zoom) mapKelas.setView(posisi, 18);
    updateLabelRadius(nilai);
}

function updatePreviewKoordinat(nilai) {
    document.getElementById('previewLat').textContent = nilai.lat === null ? '-' : nilai.lat.toFixed(8);
    document.getElementById('previewLng').textContent = nilai.lng === null ? '-' : nilai.lng.toFixed(8);
    document.getElementById('previewRadius').textContent = `${nilai.radius} meter`;
    document.getElementById('radiusBadge').textContent = `${nilai.radius} meter`;
    document.getElementById('radiusRange').value = nilai.radius;
    updateStatusGpsKelas(nilai);
}

function updateStatusGpsKelas(nilai) {
    const status = document.getElementById('statusGpsKelas');
    const sumber = sumberKoordinat();
    if (nilai.lat === null || nilai.lng === null) {
        status.className = 'inline-flex items-center gap-2 rounded-full border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-500';
        status.innerHTML = `<span class="w-2 h-2 rounded-full bg-slate-300"></span>${sumber === 'sekolah' ? 'GPS sekolah belum diatur' : 'GPS kelas belum diatur'}`;
        return;
    }
    status.className = 'inline-flex items-center gap-2 rounded-full border border-green-200 bg-green-50 px-3 py-1.5 text-xs font-semibold text-green-700';
    status.innerHTML = `<span class="w-2 h-2 rounded-full bg-green-500"></span>${sumber === 'sekolah' ? 'GPS sekolah aktif' : 'GPS kelas aktif'}`;
}

function updateLabelRadius(nilai) {
    const titikLabel = titikRadiusLabel(nilai.lat, nilai.lng, nilai.radius);
    const teks = `Radius ${nilai.radius} m`;
    if (!labelRadius) {
        labelRadius = L.tooltip({
            permanent: true,
            direction: 'center',
            className: 'map-pin-label',
            opacity: 1,
        }).setLatLng(titikLabel).setContent(teks).addTo(mapKelas);
    } else {
        labelRadius.setLatLng(titikLabel).setContent(teks);
    }
}

function titikRadiusLabel(lat, lng, radiusMeter) {
    const earthRadius = 6378137;
    const offsetLng = (radiusMeter / earthRadius) * (180 / Math.PI) / Math.cos(lat * Math.PI / 180);
    return [lat, lng + offsetLng];
}

function initMapKelas() {
    if (typeof L === 'undefined') {
        document.getElementById('infoMapKelas').textContent = 'Leaflet gagal dimuat. Isi koordinat secara manual.';
        return;
    }

    const awal = nilaiKoordinat();
    const center = awal.lat !== null && awal.lng !== null ? [awal.lat, awal.lng] : [DEFAULT_LAT, DEFAULT_LNG];
    markerIconKelas = L.divIcon({
        className: '',
        html: '<div class="kelas-marker"></div>',
        iconSize: [30, 30],
        iconAnchor: [15, 30],
    });
    mapKelas = L.map('mapKelas', { scrollWheelZoom: false }).setView(center, awal.lat !== null ? 18 : 15);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap',
    }).addTo(mapKelas);

    mapKelas.on('click', (event) => {
        if (sumberKoordinat() === 'sekolah') setSumberKoordinat('kelas');
        setKoordinat(event.latlng.lat, event.latlng.lng);
    });
    updateMapKelas(false);
    setTimeout(() => mapKelas.invalidateSize(), 150);
}

function updateModeKoordinat() {
    const sumber = sumberKoordinat();
    const panel = document.getElementById('panelKoordinatKelas');
    const bantuan = document.getElementById('teksBantuanPeta');
    const info = document.getElementById('infoMapKelas');
    const modeKelas = sumber === 'kelas';

    panel.classList.toggle('hidden', !modeKelas);
    document.getElementById('latitude').required = modeKelas;
    document.getElementById('longitude').required = modeKelas;
    document.getElementById('radius').required = modeKelas;

    bantuan.textContent = modeKelas
        ? 'Pin biru adalah titik ruang kelas. Area biru adalah radius absensi kelas.'
        : 'Peta menampilkan titik sekolah. Klik peta jika kelas ini perlu titik sendiri.';
    info.textContent = modeKelas
        ? 'Klik peta, geser pin, atau gunakan lokasi saat ini.'
        : 'Kelas ini mengikuti koordinat sekolah dari menu Pengaturan.';

    updateMapKelas(false);
}

function isiFormEdit(id, nama, tahun, sumber, latitude, longitude, radius) {
    document.getElementById('inputId').value    = id;
    document.getElementById('nama').value       = nama;
    document.getElementById('tahun').value      = tahun;
    setSumberKoordinat(sumber || (latitude && longitude ? 'kelas' : 'sekolah'));
    document.getElementById('latitude').value   = latitude;
    document.getElementById('longitude').value  = longitude;
    setRadiusValue(radius || 50);
    document.getElementById('judulForm').textContent = 'Edit Kelas';
    document.getElementById('nama').focus();
    updateMapKelas(true);
}
function resetForm() {
    document.getElementById('formKelas').reset();
    document.getElementById('inputId').value = '';
    document.getElementById('judulForm').textContent = 'Tambah Kelas';
    setRadiusValue(50);
    setSumberKoordinat('sekolah');
    updateMapKelas(false);
}

document.getElementById('latitude').addEventListener('input', () => updateMapKelas(false));
document.getElementById('longitude').addEventListener('input', () => updateMapKelas(false));
document.getElementById('radius').addEventListener('input', (event) => {
    document.getElementById('radiusRange').value = clampRadius(event.target.value);
    updateMapKelas(false);
});
document.getElementById('radius').addEventListener('change', (event) => {
    setRadiusValue(event.target.value);
    updateMapKelas(false);
});
document.getElementById('radiusRange').addEventListener('input', (event) => {
    setRadiusValue(event.target.value);
    updateMapKelas(false);
});
document.querySelectorAll('input[name="sumber_koordinat"]').forEach((radio) => {
    radio.addEventListener('change', updateModeKoordinat);
});

document.getElementById('btnLokasiSaatIni').addEventListener('click', () => {
    const info = document.getElementById('infoMapKelas');
    if (!navigator.geolocation) {
        info.textContent = 'Browser tidak mendukung pengambilan lokasi.';
        return;
    }
    info.textContent = 'Mengambil lokasi saat ini...';
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            setSumberKoordinat('kelas');
            setKoordinat(pos.coords.latitude, pos.coords.longitude);
            info.textContent = `Lokasi saat ini dipakai. Akurasi GPS sekitar ${Math.round(pos.coords.accuracy)} meter.`;
        },
        () => {
            info.textContent = 'Gagal mengambil lokasi. Pastikan izin lokasi browser diaktifkan.';
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
});

document.getElementById('btnResetKoordinat').addEventListener('click', () => {
    document.getElementById('latitude').value = '';
    document.getElementById('longitude').value = '';
    document.getElementById('radius').value = '50';
    document.getElementById('infoMapKelas').textContent = 'Koordinat dikosongkan. Klik peta atau gunakan lokasi saat ini untuk mengisi ulang.';
    updateMapKelas(false);
});

initMapKelas();
updateModeKoordinat();
</script>
