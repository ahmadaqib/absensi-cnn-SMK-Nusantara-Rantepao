/**
 * Modul absensi kamera — GPS geofencing + capture frame tiap 2 detik.
 * Polling dashboard setiap 5 detik untuk memperbarui tabel.
 */

const APP_URL      = window.__APP_URL__ || '';
const JADWAL_ID    = window.__JADWAL_ID__ || 0;
const KELAS_ADA_GPS = window.__KELAS_ADA_GPS__ || false;

const video    = document.getElementById('videoKamera');
const canvas   = document.getElementById('canvasKamera');
const elStatus = document.getElementById('statusAbsensi');
const elHasil  = document.getElementById('hasilAbsensi');
const dotGps   = document.getElementById('dotGps');
const pesanGps = document.getElementById('pesanGps');

let streamAktif     = false;
let sedangProses    = false;
let intervalCapture = null;
let intervalPolling = null;
let gpsSedangDiminta = false;

// Temporal voting — tunggu 2 frame berturut-turut sebelum tampilkan sukses ke user.
// Frame ke-1 sudah disimpan DB (duplicate check PHP mencegah ganda).
// Frame ke-2 (atau duplikat server) = konfirmasi UI.
let konfirmasiState = { nis: null, data: null };
let konfirmasiTimer = null;

// Posisi GPS siswa — diperbarui secara berkala
let posisiGps = { lat: null, lon: null, akurasi: null, lolos: !KELAS_ADA_GPS };

/* ───── GPS Geofencing ───── */
function mulaiGps() {
    if (!KELAS_ADA_GPS) {
        // Kelas tanpa koordinat — langsung aktifkan kamera
        setGpsStatus('skip');
        mulaiKamera();
        return;
    }

    if (!navigator.geolocation) {
        setGpsStatus('tidak_didukung');
        return;
    }

    setGpsStatus('memverifikasi');

    navigator.geolocation.watchPosition(
        (pos) => {
            posisiGps.lat    = pos.coords.latitude;
            posisiGps.lon    = pos.coords.longitude;
            posisiGps.akurasi = pos.coords.accuracy;
            // Status lolos/tidak ditentukan oleh server saat POST
            setGpsStatus('dapat');

            // Aktifkan kamera sekali GPS berhasil didapat
            if (!streamAktif) mulaiKamera();
        },
        (err) => {
            posisiGps.lat = null;
            posisiGps.lon = null;
            posisiGps.lolos = false;
            if (err.code === 1) {
                setGpsStatus('ditolak');
            } else {
                setGpsStatus('error_gps');
            }
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 5000 }
    );
}

function gpsSiap() {
    return Number.isFinite(posisiGps.lat) && Number.isFinite(posisiGps.lon);
}

function ambilGpsSekali() {
    if (!KELAS_ADA_GPS || gpsSiap() || gpsSedangDiminta) {
        return Promise.resolve(gpsSiap());
    }

    if (!navigator.geolocation) {
        setGpsStatus('tidak_didukung');
        return Promise.resolve(false);
    }

    gpsSedangDiminta = true;
    setGpsStatus('memverifikasi');

    return new Promise((resolve) => {
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                posisiGps.lat     = pos.coords.latitude;
                posisiGps.lon     = pos.coords.longitude;
                posisiGps.akurasi = pos.coords.accuracy;
                gpsSedangDiminta  = false;
                setGpsStatus('dapat');
                resolve(true);
            },
            (err) => {
                gpsSedangDiminta = false;
                posisiGps.lat = null;
                posisiGps.lon = null;
                setGpsStatus(err.code === 1 ? 'ditolak' : 'error_gps');
                resolve(false);
            },
            { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
        );
    });
}

function setGpsStatus(state) {
    const config = {
        memverifikasi : { dot: 'bg-slate-400 animate-pulse', pesan: 'Memverifikasi lokasi GPS...' },
        dapat         : { dot: 'bg-blue-400', pesan: `GPS aktif (akurasi ±${Math.round(posisiGps.akurasi || 0)}m)` },
        lolos         : { dot: 'bg-green-400', pesan: '' },
        gagal         : { dot: 'bg-red-400', pesan: '' },
        ditolak       : { dot: 'bg-red-500', pesan: 'Izin GPS ditolak. Absensi tidak dapat dilanjutkan.' },
        tidak_didukung: { dot: 'bg-red-400', pesan: 'Browser tidak mendukung GPS.' },
        error_gps     : { dot: 'bg-amber-400', pesan: 'GPS tidak tersedia. Coba refresh halaman.' },
        skip          : { dot: 'bg-slate-300', pesan: 'GPS tidak dikonfigurasi untuk kelas ini.' },
    };
    const c = config[state] || config.skip;
    dotGps.className = `w-2.5 h-2.5 rounded-full flex-shrink-0 ${c.dot}`;
    if (c.pesan) pesanGps.textContent = c.pesan;
}

/* ───── Inisialisasi kamera ───── */
async function mulaiKamera() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: { width: 640, height: 480, facingMode: 'user' }
        });
        video.srcObject = stream;
        await video.play();
        streamAktif = true;
        setBingkai('mencari');
        setStatus('Mencari wajah...');
        intervalCapture = setInterval(captureFrame, 2000);
    } catch (err) {
        setStatus('Gagal mengakses kamera: ' + err.message, 'error');
        setBingkai('error');
    }
}

/* ───── Capture & kirim frame ───── */
async function captureFrame() {
    if (!streamAktif || sedangProses || !JADWAL_ID) return;

    // Jika kelas ada GPS tapi posisi belum didapat, coba ambil sekali.
    // Frame tetap dikirim agar server bisa memberi rekomendasi kelas jika wajah valid
    // tetapi siswa memilih kelas yang salah.
    if (KELAS_ADA_GPS && !gpsSiap()) {
        setStatus('Menunggu lokasi GPS...', '');
        await ambilGpsSekali();
    }

    sedangProses = true;
    mulaiVisCnn();

    canvas.width  = video.videoWidth  || 640;
    canvas.height = video.videoHeight || 480;
    canvas.getContext('2d').drawImage(video, 0, 0);
    const base64 = canvas.toDataURL('image/jpeg', 0.85);

    const form = new FormData();
    form.append('gambar',    base64);
    form.append('jadwal_id', JADWAL_ID);
    form.append('latitude',  posisiGps.lat ?? '');
    form.append('longitude', posisiGps.lon ?? '');

    try {
        const res  = await fetch(APP_URL + '/absensi/proses', { method: 'POST', body: form });
        const data = await res.json();
        tanganiHasil(data);
    } catch (_) {
        setStatus('Koneksi ke server gagal. Mencoba ulang...', 'error');
    } finally {
        sedangProses = false;
    }
}

/* ───── Temporal voting helpers ───── */
function resetKonfirmasi() {
    clearTimeout(konfirmasiTimer);
    konfirmasiState = { nis: null, data: null };
}

function tampilkanBerhasil(data) {
    clearTimeout(konfirmasiTimer);
    setBingkai('berhasil');
    setStatus('Absensi berhasil!', 'sukses');
    tampilkanKonfirmasi(data);
    if (KELAS_ADA_GPS && data.jarak !== null) {
        setGpsStatus('lolos');
        pesanGps.textContent = `Dalam area kelas (${data.jarak} m dari kelas)`;
        dotGps.classList.remove('animate-pulse');
    }
    jedaCapture(4000);
    resetKonfirmasi();
}

/* ───── Proses hasil dari server ───── */
function tanganiHasil(data) {
    // Tampilkan confidence score di visualisasi CNN jika ada
    if (typeof data.confidence === 'number') selesaiVisCnn(data.confidence);

    switch (data.status) {
        case 'berhasil':
            if (konfirmasiState.nis === data.nis) {
                // Frame ke-2 untuk NIS yang sama → konfirmasi penuh
                tampilkanBerhasil(data);
            } else {
                // Frame ke-1 — DB sudah tersimpan, tunggu konfirmasi
                konfirmasiState = { nis: data.nis, data: data };
                setBingkai('peringatan');
                setStatus('Wajah dikenali, mengkonfirmasi...', 'peringatan');
                // Fallback: tampilkan sukses setelah 5 detik jika frame ke-2 tidak datang
                konfirmasiTimer = setTimeout(() => tampilkanBerhasil(data), 5000);
            }
            break;

        case 'duplikat':
            if (konfirmasiState.nis === data.nis) {
                // Server mengembalikan duplikat setelah frame ke-1 berhasil → konfirmasi UI
                tampilkanBerhasil(konfirmasiState.data || data);
            } else {
                resetKonfirmasi();
                setBingkai('peringatan');
                setStatus(data.pesan, 'peringatan');
                jedaCapture(3000);
            }
            break;

        case 'error_gps':
            resetKonfirmasi();
            setBingkai('error');
            setStatus(data.pesan, 'error');
            setGpsStatus('gagal');
            pesanGps.textContent = `Di luar area kelas (${data.jarak} m dari kelas, maks ${window.__RADIUS__ || 50} m)`;
            setTimeout(() => setBingkai('mencari'), 3000);
            break;

        case 'gps_belum_siap':
            setBingkai('peringatan');
            setStatus(data.pesan, 'peringatan');
            setGpsStatus('memverifikasi');
            posisiGps.lat = null;
            posisiGps.lon = null;
            ambilGpsSekali();
            setTimeout(() => setBingkai('mencari'), 2500);
            break;

        case 'salah_kelas':
            resetKonfirmasi();
            setBingkai('peringatan');
            setStatus(data.pesan, 'peringatan');
            tampilkanRekomendasiKelas(data);
            jedaCapture(5000);
            break;

        case 'gagal':
            // Jangan reset konfirmasi saat gagal — bisa karena frame transisi,
            // frame ke-2 mungkin masih berhasil jika wajah kembali fokus
            setBingkai('gagal');
            setStatus(formatPesanGagal(data), 'error');
            setTimeout(() => setBingkai('mencari'), 2000);
            break;

        case 'error':
            resetKonfirmasi();
            setStatus(data.pesan, 'error');
            setBingkai('error');
            break;

        default:
            setStatus('Mencari wajah...', '');
            setBingkai('mencari');
    }
}

/* ───── UI helpers ───── */
const _HINT_TEKS = {
    mencari   : 'Posisikan wajah dalam kotak',
    berhasil  : 'Wajah dikenali ✓',
    peringatan: 'Mengkonfirmasi…',
    gagal     : 'Tidak dikenali — coba lagi',
    error     : '',
};

function setBingkai(state) {
    // Satu data-state mengontrol warna korner + hint + scanner via CSS
    const zona = document.getElementById('zonaWajah');
    if (zona) zona.dataset.state = state;

    const hint = document.getElementById('hintWajah');
    if (hint) hint.textContent = _HINT_TEKS[state] ?? '';
}

function setStatus(pesan, tipe = '') {
    const warna = {
        sukses    : 'text-green-300',
        peringatan: 'text-amber-300',
        error     : 'text-red-300',
    };
    elStatus.textContent = pesan;
    elStatus.className   = 'text-sm text-center mt-2 ' + (warna[tipe] || 'text-slate-300');
}

function formatPesanGagal(data) {
    if (typeof data.confidence === 'number') {
        return `${data.pesan} Confidence ${Math.round(data.confidence * 100)}%.`;
    }
    return data.pesan || 'Wajah tidak dikenali.';
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function tampilkanKonfirmasi(data) {
    const labelStatus = { hadir: 'Hadir', terlambat: 'Terlambat' };
    const warnaBadge  = {
        hadir    : 'bg-green-50 text-green-800 border border-green-200',
        terlambat: 'bg-amber-50 text-amber-800 border border-amber-200',
    };

    const item = document.createElement('div');
    item.className = 'flex items-center justify-between bg-white border border-slate-200 rounded-lg px-4 py-3';
    item.innerHTML = `
        <div>
            <p class="font-semibold text-slate-900 text-sm">${data.nama_siswa}</p>
            <p class="text-xs text-slate-400">${data.nis} · ${data.jam} WIT</p>
        </div>
        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium ${warnaBadge[data.status_absensi] || ''}">
            ${labelStatus[data.status_absensi] || data.status_absensi}
        </span>
    `;

    elHasil.insertBefore(item, elHasil.firstChild);
    while (elHasil.children.length > 5) elHasil.removeChild(elHasil.lastChild);
}

function tampilkanRekomendasiKelas(data) {
    const item = document.createElement('div');
    item.className = 'bg-amber-50 border border-amber-200 rounded-lg px-4 py-3';
    item.innerHTML = `
        <p class="font-semibold text-amber-900 text-sm">${escapeHtml(data.nama_siswa)}</p>
        <p class="text-xs text-amber-700 mt-1">
            NIS ${escapeHtml(data.nis)} terdaftar di kelas ${escapeHtml(data.nama_kelas)}.
        </p>
        <a href="${escapeHtml(data.redirect_url)}"
           class="mt-3 inline-flex h-8 items-center px-3 rounded-md bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold">
            Pilih kelas ${escapeHtml(data.nama_kelas)}
        </a>
    `;

    elHasil.insertBefore(item, elHasil.firstChild);
    while (elHasil.children.length > 5) elHasil.removeChild(elHasil.lastChild);
}

function jedaCapture(ms) {
    clearInterval(intervalCapture);
    setTimeout(() => {
        setBingkai('mencari');
        setStatus('Mencari wajah...');
        intervalCapture = setInterval(captureFrame, 2000);
    }, ms);
}

/* ───── Polling dashboard (setiap 5 detik) ───── */
function mulaiPolling() {
    const muat = async () => {
        try {
            const res  = await fetch(APP_URL + '/absensi/rekap/data');
            const data = await res.json();
            perbaruiTabelDashboard(data.data || []);
        } catch (_) {
            tampilkanStatusTabelLive('Gagal memuat data terbaru.');
        }
    };

    muat();
    intervalPolling = setInterval(muat, 5000);
}

function perbaruiTabelDashboard(rows) {
    const tabel = document.getElementById('tabelAbsensiLive');
    if (!tabel) return;
    if (rows.length === 0) {
        tampilkanStatusTabelLive('Belum ada absensi final hari ini.');
        return;
    }

    const badgeKelas = {
        hadir      : 'badge-hadir',
        terlambat  : 'badge-terlambat',
        tidak_hadir: 'badge-tidak-hadir',
    };
    const labelSt = { hadir: 'Hadir', terlambat: 'Terlambat', tidak_hadir: 'Tidak Hadir' };

    tabel.innerHTML = rows.map((a, i) => `
        <tr class="border-b border-slate-100 ${i % 2 ? 'bg-slate-50/50' : ''}">
            <td class="px-4 py-2.5">
                <p class="font-medium text-slate-900 text-sm">${escapeHtml(a.nama_siswa)}</p>
                <p class="text-xs text-slate-400">${escapeHtml(a.nis)}</p>
            </td>
            <td class="px-4 py-2.5 text-slate-600 text-xs">${escapeHtml(a.nama_kelas)}</td>
            <td class="px-4 py-2.5 font-mono text-slate-600 text-xs">${escapeHtml(String(a.jam || '').substring(0, 5))}</td>
            <td class="px-4 py-2.5">
                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium ${badgeKelas[a.status] || ''}">
                    ${escapeHtml(labelSt[a.status] || a.status)}
                </span>
            </td>
        </tr>
    `).join('');
}

function tampilkanStatusTabelLive(pesan) {
    const tabel = document.getElementById('tabelAbsensiLive');
    if (!tabel) return;
    tabel.innerHTML = `
        <tr>
            <td colspan="4" class="text-slate-300 py-3 text-center">${escapeHtml(pesan)}</td>
        </tr>
    `;
}

/* ───── Visualisasi Pipeline CNN ───── */
let _timerVisCnn = [];

function _setupVisCnn() {
    // Generate 36 sel untuk feature map grid (6×6)
    const fmGrid = document.getElementById('cnnFmGrid');
    if (fmGrid && fmGrid.childElementCount === 0) {
        const palet = ['#DBEAFE','#BFDBFE','#93C5FD','#60A5FA','#3B82F6','#2563EB'];
        for (let i = 0; i < 36; i++) {
            const c = document.createElement('div');
            c.className = 'cnn-fm-cell rounded-sm';
            c.style.backgroundColor = palet[i % palet.length];
            // Delay stagger: simulasi konvolusi kernel berjalan diagonal
            c.style.animationDelay = `${((Math.floor(i / 6) + (i % 6)) * 0.06).toFixed(2)}s`;
            fmGrid.appendChild(c);
        }
    }

    // Grid 3×3 sebelum pooling
    const poolBefore = document.getElementById('cnnPoolBefore');
    if (poolBefore && poolBefore.childElementCount === 0) {
        ['#BFDBFE','#93C5FD','#60A5FA',
         '#3B82F6','#93C5FD','#BFDBFE',
         '#60A5FA','#3B82F6','#93C5FD'].forEach(warna => {
            const d = document.createElement('div');
            d.className = 'rounded-sm';
            d.style.backgroundColor = warna;
            poolBefore.appendChild(d);
        });
    }

    // Grid 2×2 setelah pooling (lebih pekat = compressed)
    const poolAfter = document.getElementById('cnnPoolAfter');
    if (poolAfter && poolAfter.childElementCount === 0) {
        ['#60A5FA','#2563EB','#3B82F6','#1D4ED8'].forEach(warna => {
            const d = document.createElement('div');
            d.className = 'rounded-sm';
            d.style.backgroundColor = warna;
            poolAfter.appendChild(d);
        });
    }
}

function mulaiVisCnn() {
    const panel = document.getElementById('panelVisCnn');
    if (!panel) return;

    // Bersihkan timer sebelumnya dan reset semua state visual
    _timerVisCnn.forEach(t => clearTimeout(t));
    _timerVisCnn = [];

    ['cnnStage1','cnnStage2','cnnStage3'].forEach(id =>
        document.getElementById(id)?.classList.remove('cnn-aktif')
    );
    ['cnnArrow1','cnnArrow2'].forEach(id =>
        document.getElementById(id)?.classList.remove('cnn-aktif')
    );
    document.querySelectorAll('.cnn-fm-cell').forEach(c => c.classList.remove('cnn-animasi'));
    document.getElementById('cnnPoolAfter')?.classList.remove('cnn-aktif');
    document.querySelectorAll('.cnn-bar').forEach(b => b.style.height = '8%');
    const hasilEl = document.getElementById('cnnHasilConf');
    if (hasilEl) hasilEl.classList.add('hidden');
    const barEl = document.getElementById('cnnBarConf');
    if (barEl) barEl.style.width = '0%';
    const faseEl = document.getElementById('cnnFaseLabel');
    if (faseEl) faseEl.textContent = '';

    panel.classList.remove('hidden');

    // Tahap 1 — Konvolusi: feature map bercahaya dalam pola diagonal
    _timerVisCnn.push(setTimeout(() => {
        document.getElementById('cnnStage1')?.classList.add('cnn-aktif');
        document.querySelectorAll('.cnn-fm-cell').forEach(c => c.classList.add('cnn-animasi'));
        if (faseEl) faseEl.textContent = '1/3 Konvolusi';
    }, 0));

    // Tahap 2 — Pooling: grid besar mengecil menjadi grid kompak
    _timerVisCnn.push(setTimeout(() => {
        document.getElementById('cnnArrow1')?.classList.add('cnn-aktif');
        document.getElementById('cnnStage2')?.classList.add('cnn-aktif');
        document.getElementById('cnnPoolAfter')?.classList.add('cnn-aktif');
        if (faseEl) faseEl.textContent = '2/3 Pooling';
    }, 560));

    // Tahap 3 — Fully Connected: bar probabilitas naik (softmax simulasi)
    _timerVisCnn.push(setTimeout(() => {
        document.getElementById('cnnArrow2')?.classList.add('cnn-aktif');
        document.getElementById('cnnStage3')?.classList.add('cnn-aktif');
        if (faseEl) faseEl.textContent = '3/3 Klasifikasi';

        // Animasi bar secara berurutan: bar tengah (winner) paling tinggi
        const barHeights = [18, 36, 80, 28, 12];
        document.querySelectorAll('.cnn-bar').forEach((bar, i) => {
            _timerVisCnn.push(setTimeout(() => {
                bar.style.height = barHeights[i] + '%';
            }, i * 90));
        });
    }, 1080));
}

function selesaiVisCnn(confidence) {
    if (typeof confidence !== 'number') return;

    const hasilEl = document.getElementById('cnnHasilConf');
    const barEl   = document.getElementById('cnnBarConf');
    const nilaiEl = document.getElementById('cnnNilaiConf');
    if (!hasilEl) return;

    hasilEl.classList.remove('hidden');
    const pct = Math.round(confidence * 100);

    // Delay kecil agar CSS transition tertrigger
    const t = setTimeout(() => {
        if (nilaiEl) nilaiEl.textContent = pct + '%';
        if (barEl) {
            barEl.style.width = pct + '%';
            barEl.style.backgroundColor =
                pct >= 85 ? '#15803D' :
                pct >= 70 ? '#B45309' : '#B91C1C';
        }
        // Sesuaikan tinggi bar winner dengan nilai confidence nyata
        const winner = document.querySelector('.cnn-bar-winner');
        if (winner) winner.style.height = Math.max(pct, 30) + '%';
    }, 80);

    _timerVisCnn.push(t);
}

/* ───── Entry point ───── */
document.addEventListener('DOMContentLoaded', () => {
    _setupVisCnn();
    if (video) mulaiGps();
    mulaiPolling();
});
