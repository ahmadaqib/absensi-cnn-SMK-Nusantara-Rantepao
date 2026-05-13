/**
 * Modul absensi kamera — capture frame tiap 2 detik, kirim ke PHP.
 * Polling dashboard setiap 5 detik untuk memperbarui tabel.
 */

const APP_URL   = window.__APP_URL__ || '';
const JADWAL_ID = window.__JADWAL_ID__ || 0;

const video     = document.getElementById('videoKamera');
const canvas    = document.getElementById('canvasKamera');
const bingkai   = document.getElementById('bingkaiWajah');
const elStatus  = document.getElementById('statusAbsensi');
const elHasil   = document.getElementById('hasilAbsensi');

let streamAktif     = false;
let sedangProses    = false;
let intervalCapture = null;
let intervalPolling = null;

/* ───── Inisialisasi kamera ───── */
async function mulaiKamera() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: { width: 640, height: 480, facingMode: 'user' }
        });
        video.srcObject = stream;
        await video.play();
        streamAktif = true;
        setBingkai('mencari');   // abu
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
    sedangProses = true;

    canvas.width  = video.videoWidth  || 640;
    canvas.height = video.videoHeight || 480;
    canvas.getContext('2d').drawImage(video, 0, 0);
    const base64 = canvas.toDataURL('image/jpeg', 0.80);

    const form = new FormData();
    form.append('gambar',    base64);
    form.append('jadwal_id', JADWAL_ID);

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

/* ───── Proses hasil dari server ───── */
function tanganiHasil(data) {
    switch (data.status) {
        case 'berhasil':
            setBingkai('berhasil');
            setStatus('Absensi berhasil!', 'sukses');
            tampilkanKonfirmasi(data);
            jedaCapture(4000); // jeda 4 detik sebelum scan lagi
            break;

        case 'duplikat':
            setBingkai('peringatan');
            setStatus(data.pesan, 'peringatan');
            jedaCapture(3000);
            break;

        case 'gagal':
            setBingkai('gagal');
            setStatus(data.pesan, 'error');
            setTimeout(() => setBingkai('mencari'), 2000);
            break;

        case 'error':
            setStatus(data.pesan, 'error');
            setBingkai('error');
            break;

        default:
            setStatus('Mencari wajah...', '');
            setBingkai('mencari');
    }
}

/* ───── UI helpers ───── */
function setBingkai(state) {
    const kelas = {
        mencari  : 'border-white/50',
        berhasil : 'border-green-400',
        gagal    : 'border-red-400',
        peringatan: 'border-amber-400',
        error    : 'border-red-600',
    };
    bingkai.className = bingkai.className.replace(/border-\S+/g, '');
    bingkai.classList.add('border-2', 'border-dashed', kelas[state] || 'border-white/50');
}

function setStatus(pesan, tipe = '') {
    const warna = {
        sukses   : 'text-green-300',
        peringatan: 'text-amber-300',
        error    : 'text-red-300',
    };
    elStatus.textContent  = pesan;
    elStatus.className    = 'text-sm text-center mt-2 ' + (warna[tipe] || 'text-slate-300');
}

function tampilkanKonfirmasi(data) {
    const labelStatus = {
        hadir    : 'Hadir',
        terlambat: 'Terlambat',
    };
    const warnaBadge = {
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

    // Sisipkan di atas, hapus jika lebih dari 5 entri
    elHasil.insertBefore(item, elHasil.firstChild);
    while (elHasil.children.length > 5) {
        elHasil.removeChild(elHasil.lastChild);
    }
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
    intervalPolling = setInterval(async () => {
        try {
            const res  = await fetch(APP_URL + '/absensi/rekap/data');
            const data = await res.json();
            perbaruiTabelDashboard(data.data || []);
        } catch (_) { /* diabaikan */ }
    }, 5000);
}

function perbaruiTabelDashboard(rows) {
    const tabel = document.getElementById('tabelAbsensiLive');
    if (!tabel || rows.length === 0) return;

    const badgeKelas = {
        hadir       : 'badge-hadir',
        terlambat   : 'badge-terlambat',
        tidak_hadir : 'badge-tidak-hadir',
    };
    const labelSt = { hadir:'Hadir', terlambat:'Terlambat', tidak_hadir:'Tidak Hadir' };

    tabel.innerHTML = rows.map((a, i) => `
        <tr class="border-b border-slate-100 ${i % 2 ? 'bg-slate-50/50' : ''}">
            <td class="px-4 py-2.5">
                <p class="font-medium text-slate-900 text-sm">${a.nama_siswa}</p>
                <p class="text-xs text-slate-400">${a.nis}</p>
            </td>
            <td class="px-4 py-2.5 text-slate-600 text-xs">${a.nama_kelas}</td>
            <td class="px-4 py-2.5 font-mono text-slate-600 text-xs">${a.jam.substring(0,5)}</td>
            <td class="px-4 py-2.5">
                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium ${badgeKelas[a.status] || ''}">
                    ${labelSt[a.status] || a.status}
                </span>
            </td>
        </tr>
    `).join('');
}

/* ───── Entry point ───── */
document.addEventListener('DOMContentLoaded', () => {
    if (video) mulaiKamera();
    mulaiPolling();
});
