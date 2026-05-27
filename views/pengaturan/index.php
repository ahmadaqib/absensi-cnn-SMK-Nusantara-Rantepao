<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<style>
    #mapSekolah { height: clamp(360px, 52vh, 620px); min-height: 360px; }
    .leaflet-container { font-family: Inter, sans-serif; }
    .sekolah-marker {
        width: 32px;
        height: 32px;
        border-radius: 999px;
        background: #1E40AF;
        border: 4px solid #fff;
        box-shadow: 0 12px 26px rgba(15, 23, 42, .28);
    }
</style>

<?php
$latSekolah = $koordinatSekolah['latitude'] ?? '';
$lngSekolah = $koordinatSekolah['longitude'] ?? '';
$radiusSekolah = (int) ($koordinatSekolah['radius'] ?? RADIUS_MAKSIMAL);
$sekolahSiap = $latSekolah !== '' && $lngSekolah !== '';
?>

<div class="max-w-6xl space-y-6">
    <section class="bg-white border border-slate-200 rounded-lg p-5">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
            <div>
                <p class="text-xs font-semibold text-[#1E40AF] uppercase tracking-wide">Pengaturan Sistem</p>
                <h2 class="text-xl font-bold text-slate-900 mt-1">Koordinat Sekolah</h2>
                <p class="text-sm text-slate-600 mt-2 leading-6 max-w-3xl">
                    Titik ini dipakai oleh kelas yang memilih <b>Ikuti koordinat sekolah</b>. Gunakan ini jika absensi cukup divalidasi dari area sekolah, bukan dari titik setiap ruang kelas.
                </p>
            </div>
            <div class="<?= $sekolahSiap ? 'bg-green-50 border-green-200 text-green-800' : 'bg-amber-50 border-amber-200 text-amber-800' ?> border rounded-lg px-4 py-3 text-sm">
                <p class="font-semibold"><?= $sekolahSiap ? 'Koordinat sekolah aktif' : 'Koordinat sekolah belum lengkap' ?></p>
                <p class="text-xs mt-1 opacity-80"><?= $sekolahSiap ? 'Kelas dapat mengikuti titik sekolah.' : 'Isi titik sekolah sebelum kelas memakai mode sekolah.' ?></p>
            </div>
        </div>
    </section>

    <section class="bg-white border border-slate-200 rounded-lg overflow-hidden">
        <form method="POST" action="<?= APP_URL ?>/pengaturan/sekolah" id="formSekolah">
            <div class="grid grid-cols-1 xl:grid-cols-12">
                <div class="xl:col-span-4 p-5 border-b xl:border-b-0 xl:border-r border-slate-200 space-y-5">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Titik pusat geofencing sekolah</h3>
                        <p id="infoMapSekolah" class="text-xs text-slate-500 mt-1">Klik peta, geser marker, atau gunakan lokasi saat ini.</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-1 gap-3">
                        <div>
                            <label for="latitude" class="block text-xs font-medium text-slate-600 mb-1">Latitude</label>
                            <input type="text" id="latitude" name="latitude" required value="<?= htmlspecialchars($latSekolah) ?>" placeholder="-3.948250"
                                   class="w-full h-9 px-2 text-sm font-mono border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1E40AF]">
                        </div>
                        <div>
                            <label for="longitude" class="block text-xs font-medium text-slate-600 mb-1">Longitude</label>
                            <input type="text" id="longitude" name="longitude" required value="<?= htmlspecialchars($lngSekolah) ?>" placeholder="119.899870"
                                   class="w-full h-9 px-2 text-sm font-mono border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1E40AF]">
                        </div>
                    </div>

                    <div class="rounded-lg border border-slate-200 p-3 bg-slate-50">
                        <div class="flex items-center justify-between gap-3 mb-2">
                            <label for="radius" class="text-xs font-medium text-slate-600">Radius Sekolah</label>
                            <span id="radiusBadge" class="px-2 py-1 rounded-full bg-blue-50 text-[#1E40AF] text-xs font-semibold"><?= $radiusSekolah ?> meter</span>
                        </div>
                        <div class="grid grid-cols-[1fr_92px] gap-3 items-center">
                            <input type="range" id="radiusRange" min="10" max="1000" step="5" value="<?= $radiusSekolah ?>" class="w-full accent-[#1E40AF]">
                            <input type="number" id="radius" name="radius" value="<?= $radiusSekolah ?>" min="10" max="1000"
                                   class="w-full h-9 px-2 text-sm border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-200 focus:border-[#1E40AF]">
                        </div>
                        <div class="flex justify-between mt-1 text-[10px] text-slate-400">
                            <span>10 m</span>
                            <span>1000 m</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-1 gap-2">
                        <button type="button" id="btnLokasiSaatIni"
                                class="h-10 px-3 text-sm font-semibold text-[#1E40AF] border border-blue-200 rounded-md hover:bg-blue-50 transition-colors">
                            Gunakan Lokasi Saat Ini
                        </button>
                        <button type="submit"
                                class="h-10 px-3 text-sm font-semibold text-white bg-[#1E40AF] hover:bg-[#1D4ED8] rounded-md transition-colors">
                            Simpan Koordinat Sekolah
                        </button>
                    </div>
                </div>

                <div class="xl:col-span-8 p-5 space-y-3">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Peta Sekolah</h3>
                            <p class="text-sm text-slate-500">Marker biru adalah titik sekolah. Area biru adalah radius geofencing sekolah.</p>
                        </div>
                        <div id="statusGpsSekolah" class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-500">
                            <span class="w-2 h-2 rounded-full bg-slate-300"></span>
                            GPS belum diatur
                        </div>
                    </div>

                    <div class="relative rounded-lg border border-slate-200 overflow-hidden bg-slate-100">
                        <div id="mapSekolah" class="w-full"></div>
                        <div class="absolute right-3 top-3 z-[500] max-w-[calc(100%-1.5rem)] rounded-md border border-blue-100 bg-white/95 px-3 py-2 shadow-sm pointer-events-none">
                            <p class="text-xs font-semibold text-slate-900">Klik peta untuk memilih titik sekolah</p>
                            <p class="text-[11px] text-slate-500 mt-0.5">Marker bisa digeser setelah dipasang.</p>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const DEFAULT_LAT = -3.948250;
const DEFAULT_LNG = 119.899870;
let mapSekolah;
let markerSekolah = null;
let circleSekolah = null;
let markerIconSekolah = null;

function clampRadius(value) {
    const angka = parseInt(value, 10);
    if (!Number.isFinite(angka)) return <?= RADIUS_MAKSIMAL ?>;
    return Math.min(1000, Math.max(10, angka));
}

function nilaiKoordinat() {
    const lat = parseFloat(document.getElementById('latitude').value);
    const lng = parseFloat(document.getElementById('longitude').value);
    return {
        lat: Number.isFinite(lat) ? lat : null,
        lng: Number.isFinite(lng) ? lng : null,
        radius: clampRadius(document.getElementById('radius').value),
    };
}

function setRadius(value) {
    const radius = clampRadius(value);
    document.getElementById('radius').value = radius;
    document.getElementById('radiusRange').value = radius;
    document.getElementById('radiusBadge').textContent = `${radius} meter`;
    updateMap(false);
}

function setKoordinat(lat, lng, zoom = true) {
    document.getElementById('latitude').value = Number(lat).toFixed(8);
    document.getElementById('longitude').value = Number(lng).toFixed(8);
    updateMap(zoom);
}

function updateStatus(nilai) {
    const status = document.getElementById('statusGpsSekolah');
    if (nilai.lat === null || nilai.lng === null) {
        status.className = 'inline-flex items-center gap-2 rounded-full border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-500';
        status.innerHTML = '<span class="w-2 h-2 rounded-full bg-slate-300"></span>GPS belum diatur';
        return;
    }
    status.className = 'inline-flex items-center gap-2 rounded-full border border-green-200 bg-green-50 px-3 py-1.5 text-xs font-semibold text-green-700';
    status.innerHTML = '<span class="w-2 h-2 rounded-full bg-green-500"></span>GPS aktif';
}

function updateMap(zoom = false) {
    if (!mapSekolah) return;
    const nilai = nilaiKoordinat();
    document.getElementById('radiusBadge').textContent = `${nilai.radius} meter`;
    updateStatus(nilai);

    if (nilai.lat === null || nilai.lng === null) {
        if (markerSekolah) markerSekolah.remove();
        if (circleSekolah) circleSekolah.remove();
        markerSekolah = null;
        circleSekolah = null;
        return;
    }

    const posisi = [nilai.lat, nilai.lng];
    if (!markerSekolah) {
        markerSekolah = L.marker(posisi, { draggable: true, icon: markerIconSekolah }).addTo(mapSekolah);
        markerSekolah.on('dragend', () => {
            const pos = markerSekolah.getLatLng();
            setKoordinat(pos.lat, pos.lng, false);
        });
    } else {
        markerSekolah.setLatLng(posisi);
    }

    if (!circleSekolah) {
        circleSekolah = L.circle(posisi, {
            radius: nilai.radius,
            color: '#1E40AF',
            weight: 2,
            fillColor: '#60A5FA',
            fillOpacity: 0.18,
        }).addTo(mapSekolah);
    } else {
        circleSekolah.setLatLng(posisi);
        circleSekolah.setRadius(nilai.radius);
    }

    if (zoom) mapSekolah.setView(posisi, 17);
}

function initMap() {
    if (typeof L === 'undefined') {
        document.getElementById('infoMapSekolah').textContent = 'Leaflet gagal dimuat. Isi koordinat secara manual.';
        return;
    }

    const awal = nilaiKoordinat();
    const center = awal.lat !== null && awal.lng !== null ? [awal.lat, awal.lng] : [DEFAULT_LAT, DEFAULT_LNG];
    markerIconSekolah = L.divIcon({
        className: '',
        html: '<div class="sekolah-marker"></div>',
        iconSize: [32, 32],
        iconAnchor: [16, 16],
    });

    mapSekolah = L.map('mapSekolah', { scrollWheelZoom: false }).setView(center, awal.lat !== null ? 17 : 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap',
    }).addTo(mapSekolah);
    mapSekolah.on('click', (event) => setKoordinat(event.latlng.lat, event.latlng.lng));
    updateMap(false);
    setTimeout(() => mapSekolah.invalidateSize(), 150);
}

document.getElementById('latitude').addEventListener('input', () => updateMap(false));
document.getElementById('longitude').addEventListener('input', () => updateMap(false));
document.getElementById('radius').addEventListener('input', (event) => {
    document.getElementById('radiusRange').value = clampRadius(event.target.value);
    updateMap(false);
});
document.getElementById('radius').addEventListener('change', (event) => setRadius(event.target.value));
document.getElementById('radiusRange').addEventListener('input', (event) => setRadius(event.target.value));
document.getElementById('btnLokasiSaatIni').addEventListener('click', () => {
    const info = document.getElementById('infoMapSekolah');
    if (!navigator.geolocation) {
        info.textContent = 'Browser tidak mendukung pengambilan lokasi.';
        return;
    }
    info.textContent = 'Mengambil lokasi saat ini...';
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            setKoordinat(pos.coords.latitude, pos.coords.longitude);
            info.textContent = `Lokasi saat ini dipakai. Akurasi GPS sekitar ${Math.round(pos.coords.accuracy)} meter.`;
        },
        () => {
            info.textContent = 'Gagal mengambil lokasi. Pastikan izin lokasi browser diaktifkan.';
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
});

initMap();
</script>
