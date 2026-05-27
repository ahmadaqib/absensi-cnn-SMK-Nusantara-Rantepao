/**
 * Dataset wajah Guru — kamera, simpan foto, panduan posisi.
 * Semua PHP variable diterima via window.__* (diinjeksi dataset.php).
 */

const GURU_ID     = window.__GURU_ID__;
const TARGET_FOTO = window.__TARGET_FOTO__;
const MINIMAL_FOTO = window.__MINIMAL_FOTO__;
const APP_URL     = window.__APP_URL__;
const PANDUAN     = window.__PANDUAN__;   // array panduan 10 posisi

let jumlahTersimpan = window.__JUMLAH_AWAL__;
let sedangSimpan    = false;

const video       = document.getElementById('video');
const canvas      = document.getElementById('canvas');
const btnAmbil    = document.getElementById('btnAmbil');
const statusKam   = document.getElementById('statusKamera');
const progressBar = document.getElementById('progressBar');
const pesanSt     = document.getElementById('pesanStatus');
const jmlEl       = document.getElementById('jumlahFoto');

/* ── Kamera ── */
navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480 } })
    .then(stream => {
        video.srcObject = stream;
        video.onloadedmetadata = () => {
            btnAmbil.disabled = jumlahTersimpan >= TARGET_FOTO;
            statusKam.textContent = jumlahTersimpan >= TARGET_FOTO
                ? 'Dataset sudah lengkap.'
                : 'Kamera siap. Ikuti panduan lalu klik "Ambil Foto".';
        };
    })
    .catch(() => { statusKam.textContent = 'Gagal mengakses kamera.'; });

/* ── Ambil Foto ── */
btnAmbil.addEventListener('click', () => {
    if (sedangSimpan || jumlahTersimpan >= TARGET_FOTO) return;
    sedangSimpan = true;
    btnAmbil.disabled = true;
    btnAmbil.textContent = 'Menyimpan...';

    canvas.width  = video.videoWidth  || 640;
    canvas.height = video.videoHeight || 480;
    canvas.getContext('2d').drawImage(video, 0, 0);
    const base64 = canvas.toDataURL('image/jpeg', 0.85);

    const form = new FormData();
    form.append('guru_id', GURU_ID);
    form.append('gambar',  base64);

    fetch(APP_URL + '/absensi-guru/dataset/simpan', { method: 'POST', body: form })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'ok' || data.status === 'penuh') {
                jumlahTersimpan = data.jumlah;
                jmlEl.textContent = jumlahTersimpan;
                updateProgress();
                updateSlot(jumlahTersimpan, base64);
                setSlotAktif(jumlahTersimpan + 1);
                updateKartuInstruksi();

                if (data.selesai || data.status === 'penuh') {
                    statusKam.textContent   = 'Dataset lengkap!';
                    btnAmbil.textContent    = 'Selesai';
                    pesanSt.textContent     = 'Dataset lengkap. Siap untuk training.';
                    progressBar.style.backgroundColor = '#15803D';
                    pesanSt.classList.add('text-green-700', 'font-semibold');
                } else {
                    btnAmbil.textContent = 'Ambil Foto';
                    btnAmbil.disabled    = false;
                    statusKam.textContent = `${jumlahTersimpan}/${TARGET_FOTO} foto tersimpan.`;
                }
            } else {
                btnAmbil.textContent = 'Ambil Foto';
                btnAmbil.disabled    = false;
                statusKam.textContent = data.pesan || 'Gagal menyimpan foto.';
            }
        })
        .catch(() => {
            btnAmbil.textContent = 'Ambil Foto';
            btnAmbil.disabled    = false;
            statusKam.textContent = 'Error koneksi. Coba lagi.';
        })
        .finally(() => { sedangSimpan = false; });
});

/* ── Instruksi panduan ── */
function updateKartuInstruksi() {
    const kartu = document.getElementById('kartuInstruksi');
    if (!kartu) return;

    const idx = jumlahTersimpan + 1;
    const p   = PANDUAN[idx];

    if (!p || jumlahTersimpan >= TARGET_FOTO) {
        kartu.classList.add('hidden');
        return;
    }

    kartu.classList.remove('hidden');
    const elNo     = document.getElementById('instrNomor');
    const elIkon   = document.getElementById('instrIkon');
    const elJudul  = document.getElementById('instrJudul');
    const elTeks   = document.getElementById('instrTeks');
    const elDetail = document.getElementById('instrDetail');

    if (elNo)     elNo.textContent     = `Foto ${idx} dari ${TARGET_FOTO}`;
    if (elIkon)   elIkon.textContent   = p.ikon;
    if (elJudul)  elJudul.textContent  = p.label;
    if (elTeks)   elTeks.textContent   = p.instruksi;
    if (elDetail) elDetail.textContent = p.detail;
}

/* ── Slot grid ── */
function updateSlot(nomor, base64) {
    const slot = document.getElementById('slot-' + nomor);
    if (!slot) return;
    slot.className = 'aspect-square rounded-md overflow-hidden border border-green-200 bg-green-50';
    slot.innerHTML = `<img src="${base64}" class="w-full h-full object-cover" alt="Foto ${nomor}">`;
}

function setSlotAktif(nomor) {
    for (let i = 1; i <= TARGET_FOTO; i++) {
        const slot = document.getElementById('slot-' + i);
        if (!slot || slot.querySelector('img')) continue;

        const isAktif = i === nomor;
        slot.style.borderColor = isAktif ? '#1E40AF' : '';
        slot.style.borderStyle = isAktif ? 'solid' : 'dashed';
        slot.style.background  = isAktif ? '#EFF6FF' : '';

        const inner = slot.querySelector('[data-inner]');
        if (inner) inner.style.color = isAktif ? '#1E40AF' : '';
    }
}

/* ── Progress bar ── */
function updateProgress() {
    const pct = Math.min((jumlahTersimpan / TARGET_FOTO) * 100, 100);
    progressBar.style.width = pct + '%';
    if (jumlahTersimpan >= TARGET_FOTO) {
        progressBar.style.backgroundColor = '#15803D';
    } else if (jumlahTersimpan >= MINIMAL_FOTO) {
        progressBar.style.backgroundColor = '#1E40AF';
        pesanSt.textContent = `Sudah cukup untuk training. Tambah hingga ${TARGET_FOTO} untuk hasil optimal.`;
    }
}

/* ── Dialog retake ── */
function tampilKonfirmasiRetake() {
    document.getElementById('konfirmasiJumlah').textContent = jumlahTersimpan;
    document.getElementById('dialogRetake').classList.remove('hidden');
}
// global definition so HTML inline onclick can find it
window.tampilKonfirmasiRetake = tampilKonfirmasiRetake;

function tutupKonfirmasiRetake() {
    document.getElementById('dialogRetake').classList.add('hidden');
}
window.tutupKonfirmasiRetake = tutupKonfirmasiRetake;

document.getElementById('dialogRetake')?.addEventListener('click', function(e) {
    if (e.target === this) tutupKonfirmasiRetake();
});
