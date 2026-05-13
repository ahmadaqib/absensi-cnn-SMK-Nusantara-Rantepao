<?php
$targetFoto = 10;
$sudahCukup = $jumlahFoto >= $targetFoto;
?>

<div class="max-w-3xl">
    <!-- Info siswa -->
    <div class="bg-white border border-slate-200 rounded-lg p-4 mb-6 flex items-center gap-4">
        <?php if (!empty($siswa['foto'])): ?>
        <img src="<?= APP_URL . '/public/' . htmlspecialchars($siswa['foto']) ?>"
             class="w-12 h-12 rounded-full object-cover border border-slate-200" alt="">
        <?php else: ?>
        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-lg font-bold text-slate-500">
            <?= mb_strtoupper(mb_substr($siswa['nama'], 0, 1)) ?>
        </div>
        <?php endif; ?>
        <div>
            <p class="font-semibold text-slate-900"><?= htmlspecialchars($siswa['nama']) ?></p>
            <p class="text-sm text-slate-500">NIS: <?= htmlspecialchars($siswa['nis']) ?> · <?= htmlspecialchars($siswa['nama_kelas']) ?></p>
        </div>
        <div class="ml-auto text-right">
            <p class="text-2xl font-bold <?= $sudahCukup ? 'text-[#15803D]' : 'text-[#1E40AF]' ?>">
                <span id="jumlahFoto"><?= $jumlahFoto ?></span>/<?= $targetFoto ?>
            </p>
            <p class="text-xs text-slate-400">foto tersimpan</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Kamera -->
        <div class="bg-white border border-slate-200 rounded-lg p-5">
            <h2 class="text-sm font-semibold text-slate-700 mb-3">Kamera</h2>

            <!-- Panduan -->
            <div class="bg-blue-50 border border-blue-200 rounded-md px-3 py-2 mb-3 text-xs text-blue-800">
                Variasikan posisi: frontal, sedikit ke kiri, sedikit ke kanan.
                Pastikan pencahayaan cukup.
            </div>

            <div class="relative bg-slate-900 rounded-lg overflow-hidden" style="aspect-ratio:4/3">
                <video id="video" autoplay playsinline muted
                       class="w-full h-full object-cover"></video>
                <!-- Overlay bingkai wajah -->
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div id="bingkai"
                         class="w-40 h-48 border-2 border-dashed border-white/50 rounded-xl transition-colors"></div>
                </div>
                <!-- Status overlay -->
                <div id="statusKamera"
                     class="absolute bottom-3 left-0 right-0 text-center text-xs text-white/80">
                    Memuat kamera...
                </div>
            </div>

            <canvas id="canvas" class="hidden"></canvas>

            <div class="mt-3 flex gap-2">
                <button id="btnAmbil"
                        disabled
                        class="flex-1 h-9 bg-[#1E40AF] hover:bg-[#1D4ED8] disabled:bg-slate-300
                               text-white text-sm font-semibold rounded-md transition-colors">
                    Ambil Foto
                </button>
                <?php if ($jumlahFoto > 0): ?>
                <button type="button" id="btnRetake"
                        onclick="tampilKonfirmasiRetake()"
                        class="h-9 px-3 text-xs text-red-600 border border-red-200 rounded-md hover:bg-red-50 transition-colors">
                    Retake
                </button>
                <?php endif; ?>
            </div>

            <!-- Progress bar -->
            <div class="mt-3">
                <div class="w-full bg-slate-100 rounded-full h-2">
                    <div id="progressBar"
                         class="bg-[#1E40AF] h-2 rounded-full transition-all"
                         style="width: <?= ($jumlahFoto / $targetFoto) * 100 ?>%"></div>
                </div>
                <p id="pesanStatus" class="text-xs text-slate-500 mt-1 text-center">
                    <?= $sudahCukup ? 'Dataset lengkap. Siap untuk training.' : "Butuh $targetFoto foto untuk training." ?>
                </p>
            </div>
        </div>

        <!-- Pratinjau foto tersimpan -->
        <div class="bg-white border border-slate-200 rounded-lg p-5">
            <h2 class="text-sm font-semibold text-slate-700 mb-3">Foto Tersimpan</h2>
            <div id="gridFoto" class="grid grid-cols-5 gap-1.5">
                <?php
                $dirDataset = BASE_PATH . '/python/dataset/' . $siswa['nis'] . '/';
                for ($i = 1; $i <= $targetFoto; $i++):
                    $fotoAda = is_file($dirDataset . "foto_$i.jpg");
                ?>
                <div id="slot-<?= $i ?>"
                     class="aspect-square rounded-md overflow-hidden border border-slate-200
                            <?= $fotoAda ? 'bg-green-50' : 'bg-slate-100' ?>">
                    <?php if ($fotoAda): ?>
                    <img src="<?= APP_URL ?>/python/dataset/<?= $siswa['nis'] ?>/foto_<?= $i ?>.jpg"
                         class="w-full h-full object-cover"
                         alt="Foto <?= $i ?>">
                    <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center text-slate-300 text-xs font-mono">
                        <?= $i ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endfor; ?>
            </div>

            <?php if ($sudahCukup): ?>
            <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded-md text-sm text-green-800 font-medium">
                Dataset lengkap. Lanjut ke
                <a href="<?= APP_URL ?>/training" class="underline">Training CNN</a>.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-4">
        <a href="<?= APP_URL ?>/siswa"
           class="text-sm text-slate-500 hover:text-slate-700">← Kembali ke daftar siswa</a>
    </div>
</div>

<!-- Dialog konfirmasi retake (custom, bukan browser confirm) -->
<div id="dialogRetake"
     class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="bg-white rounded-xl shadow-xl border border-slate-200 w-full max-w-sm mx-4 p-6">
        <div class="flex items-start gap-3 mb-4">
            <div class="w-10 h-10 flex-shrink-0 bg-red-100 rounded-full flex items-center justify-center">
                <svg class="w-5 h-5 text-red-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-slate-900">Hapus semua foto dataset?</p>
                <p class="text-sm text-slate-500 mt-1">
                    Semua <strong id="konfirmasiJumlah"><?= $jumlahFoto ?></strong> foto
                    <strong><?= htmlspecialchars($siswa['nama']) ?></strong> akan dihapus
                    dari folder dataset. Tindakan ini tidak bisa dibatalkan.
                </p>
            </div>
        </div>
        <div class="flex gap-2 justify-end">
            <button type="button" onclick="tutupKonfirmasiRetake()"
                    class="px-4 py-2 text-sm font-medium text-slate-600 border border-slate-300 rounded-md hover:bg-slate-50 transition-colors">
                Batal
            </button>
            <form method="POST" action="<?= APP_URL ?>/siswa/dataset/hapus" class="inline">
                <input type="hidden" name="siswa_id" value="<?= $siswa['id'] ?>">
                <button type="submit"
                        class="px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-md transition-colors">
                    Ya, Hapus Semua
                </button>
            </form>
        </div>
    </div>
</div>

<script>
const SISWA_ID    = <?= $siswa['id'] ?>;
const TARGET_FOTO = <?= $targetFoto ?>;
let jumlahTersimpan = <?= $jumlahFoto ?>;
let sedangSimpan  = false;

const video      = document.getElementById('video');
const canvas     = document.getElementById('canvas');
const btnAmbil   = document.getElementById('btnAmbil');
const bingkai    = document.getElementById('bingkai');
const statusKam  = document.getElementById('statusKamera');
const progressBar= document.getElementById('progressBar');
const pesanSt    = document.getElementById('pesanStatus');
const jmlEl      = document.getElementById('jumlahFoto');

// Mulai kamera
navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480 } })
    .then(stream => {
        video.srcObject = stream;
        video.onloadedmetadata = () => {
            btnAmbil.disabled = jumlahTersimpan >= TARGET_FOTO;
            statusKam.textContent = jumlahTersimpan >= TARGET_FOTO
                ? 'Dataset sudah lengkap.'
                : 'Kamera siap. Klik "Ambil Foto".';
            bingkai.classList.replace('border-white/50', 'border-green-400');
        };
    })
    .catch(() => {
        statusKam.textContent = 'Gagal mengakses kamera.';
        bingkai.classList.replace('border-white/50', 'border-red-400');
    });

btnAmbil.addEventListener('click', () => {
    if (sedangSimpan || jumlahTersimpan >= TARGET_FOTO) return;
    sedangSimpan = true;
    btnAmbil.disabled = true;
    btnAmbil.textContent = 'Menyimpan...';

    // Capture frame dari video
    canvas.width  = video.videoWidth  || 640;
    canvas.height = video.videoHeight || 480;
    canvas.getContext('2d').drawImage(video, 0, 0);
    const base64 = canvas.toDataURL('image/jpeg', 0.85);

    const form = new FormData();
    form.append('siswa_id', SISWA_ID);
    form.append('gambar', base64);

    fetch('<?= APP_URL ?>/siswa/dataset/simpan', { method: 'POST', body: form })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'ok' || data.status === 'penuh') {
                jumlahTersimpan = data.jumlah;
                jmlEl.textContent = jumlahTersimpan;
                updateProgress();
                updateSlot(jumlahTersimpan, base64);

                if (data.selesai || data.status === 'penuh') {
                    statusKam.textContent = 'Dataset lengkap!';
                    btnAmbil.textContent  = 'Selesai';
                    pesanSt.textContent   = 'Dataset lengkap. Siap untuk training.';
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

function updateProgress() {
    const pct = Math.min((jumlahTersimpan / TARGET_FOTO) * 100, 100);
    progressBar.style.width = pct + '%';
}

function updateSlot(nomor, base64) {
    const slot = document.getElementById('slot-' + nomor);
    if (!slot) return;
    slot.className = 'aspect-square rounded-md overflow-hidden border border-green-200 bg-green-50';
    slot.innerHTML = `<img src="${base64}" class="w-full h-full object-cover" alt="Foto ${nomor}">`;
}

function tampilKonfirmasiRetake() {
    document.getElementById('konfirmasiJumlah').textContent = jumlahTersimpan;
    document.getElementById('dialogRetake').classList.remove('hidden');
}

function tutupKonfirmasiRetake() {
    document.getElementById('dialogRetake').classList.add('hidden');
}

// Tutup dialog jika klik di luar area dialog
document.getElementById('dialogRetake').addEventListener('click', function(e) {
    if (e.target === this) tutupKonfirmasiRetake();
});
</script>
