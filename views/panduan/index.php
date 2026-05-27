<style>
.panduan-tab[aria-selected="true"] {
    background: #1E40AF;
    color: #fff;
    border-color: #1E40AF;
}
.panduan-panel { display: none; }
.panduan-panel.aktif { display: block; }
.panduan-step {
    position: relative;
    padding-left: 2.75rem;
}
.panduan-step::before {
    content: attr(data-step);
    position: absolute;
    left: 0;
    top: 0;
    width: 1.85rem;
    height: 1.85rem;
    border-radius: 999px;
    background: #DBEAFE;
    color: #1E40AF;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .78rem;
    font-weight: 700;
}
.panduan-detail[open] summary {
    color: #1E40AF;
}
.panduan-check input:checked + span {
    color: #15803D;
    text-decoration: line-through;
    text-decoration-thickness: 1px;
}
</style>

<div class="max-w-6xl space-y-6">
    <section class="bg-white border border-slate-200 rounded-lg p-5">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5">
            <div class="max-w-3xl">
                <p class="text-xs font-semibold text-[#1E40AF] uppercase tracking-wide">Panduan Operasional</p>
                <h2 class="text-2xl font-bold text-slate-900 mt-1">Dari dataset wajah sampai absensi berhasil</h2>
                <p class="text-sm text-slate-600 mt-2 leading-6">
                    Ikuti urutan ini untuk menyiapkan sistem dari nol: buat data dasar, ambil foto wajah, latih model CNN, lalu jalankan absensi kamera.
                    Panduan ini juga menjelaskan fitur tiap role dan cara kerja pipeline CNN yang dipakai aplikasi.
                </p>
            </div>
            <div class="grid grid-cols-2 gap-2 w-full lg:w-80">
                <a href="<?= APP_URL ?>/siswa" class="px-3 py-2 rounded-md border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50">Kelola Siswa</a>
                <a href="<?= APP_URL ?>/training" class="px-3 py-2 rounded-md border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50">Training CNN</a>
                <a href="<?= APP_URL ?>/absensi" class="px-3 py-2 rounded-md border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50">Absensi Kamera</a>
                <a href="<?= APP_URL ?>/laporan" class="px-3 py-2 rounded-md border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50">Laporan</a>
            </div>
        </div>
    </section>

    <section class="bg-white border border-slate-200 rounded-lg p-3">
        <div class="flex flex-wrap gap-2" role="tablist" aria-label="Bagian panduan">
            <button type="button" class="panduan-tab h-9 px-4 rounded-md border border-slate-200 text-sm font-semibold text-slate-700" data-target="panel-alur" aria-selected="true">Alur End to End</button>
            <button type="button" class="panduan-tab h-9 px-4 rounded-md border border-slate-200 text-sm font-semibold text-slate-700" data-target="panel-role" aria-selected="false">Fitur Role</button>
            <button type="button" class="panduan-tab h-9 px-4 rounded-md border border-slate-200 text-sm font-semibold text-slate-700" data-target="panel-cnn" aria-selected="false">Cara Kerja CNN</button>
            <button type="button" class="panduan-tab h-9 px-4 rounded-md border border-slate-200 text-sm font-semibold text-slate-700" data-target="panel-masalah" aria-selected="false">Cek Masalah</button>
        </div>
    </section>

    <section id="panel-alur" class="panduan-panel aktif space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div class="lg:col-span-2 bg-white border border-slate-200 rounded-lg p-5">
                <h3 class="text-sm font-bold text-slate-900 mb-4">Urutan kerja untuk admin</h3>
                <div class="space-y-6">
                    <div class="panduan-step" data-step="1">
                        <h4 class="text-sm font-semibold text-slate-900">Siapkan data dasar</h4>
                        <p class="text-sm text-slate-600 mt-1 leading-6">
                            Buka <b>Pengaturan</b> untuk mengisi titik koordinat sekolah. Lalu buka <b>Kelola Kelas</b>, isi nama kelas, tahun, dan pilih apakah kelas mengikuti koordinat sekolah atau memakai koordinat sendiri.
                            Setelah itu buka <b>Jadwal</b> dan buat jadwal mengajar sesuai kelas, guru, mata pelajaran, hari, jam mulai, dan jam selesai.
                        </p>
                    </div>

                    <div class="panduan-step" data-step="2">
                        <h4 class="text-sm font-semibold text-slate-900">Daftarkan siswa</h4>
                        <p class="text-sm text-slate-600 mt-1 leading-6">
                            Buka <b>Kelola Siswa</b>, tambah siswa, isi nama, NIS, dan kelas. NIS penting karena dipakai sebagai nama folder dataset wajah dan label hasil prediksi CNN.
                        </p>
                    </div>

                    <div class="panduan-step" data-step="3">
                        <h4 class="text-sm font-semibold text-slate-900">Ambil dataset wajah siswa</h4>
                        <p class="text-sm text-slate-600 mt-1 leading-6">
                            Di tabel siswa, klik <b>Dataset</b>. Izinkan kamera, arahkan wajah ke tengah kotak, lalu simpan sampai 10 foto.
                            Sistem sudah bisa training saat minimal 5 foto per orang, tetapi 10 foto lebih disarankan agar variasi wajah lebih lengkap.
                        </p>
                    </div>

                    <div class="panduan-step" data-step="4">
                        <h4 class="text-sm font-semibold text-slate-900">Latih model CNN</h4>
                        <p class="text-sm text-slate-600 mt-1 leading-6">
                            Buka <b>Training CNN</b>. Pastikan minimal ada 2 identitas dengan dataset cukup, lalu klik <b>Mulai Training</b>.
                            Saat selesai, aplikasi membuat <code class="font-mono text-xs">python/model_absensi.h5</code> dan <code class="font-mono text-xs">python/label_map.json</code>.
                        </p>
                    </div>

                    <div class="panduan-step" data-step="5">
                        <h4 class="text-sm font-semibold text-slate-900">Jalankan absensi siswa</h4>
                        <p class="text-sm text-slate-600 mt-1 leading-6">
                            Buka <b>Absensi Kamera</b>, pilih kelas dan jadwal hari ini. Izinkan kamera dan lokasi GPS.
                            Ketika wajah dikenali, kelas cocok, lokasi berada dalam radius, dan belum pernah absen pada jadwal itu, data langsung masuk ke rekap.
                        </p>
                    </div>

                    <div class="panduan-step" data-step="6">
                        <h4 class="text-sm font-semibold text-slate-900">Cek rekap dan laporan</h4>
                        <p class="text-sm text-slate-600 mt-1 leading-6">
                            Gunakan <b>Rekap Absensi</b> untuk memantau data harian dan <b>Laporan</b> untuk filter atau ekspor laporan.
                            Data yang sudah berhasil tersimpan akan tampil di dashboard, rekap, dan laporan.
                        </p>
                    </div>
                </div>
            </div>

            <aside class="space-y-5">
                <div class="bg-white border border-slate-200 rounded-lg p-5">
                    <h3 class="text-sm font-bold text-slate-900 mb-3">Checklist sebelum dipakai</h3>
                    <div class="space-y-2.5">
                        <label class="panduan-check flex items-start gap-2 text-sm text-slate-600">
                            <input type="checkbox" class="mt-1 rounded border-slate-300">
                            <span>Koordinat sekolah sudah diatur, atau kelas punya koordinat sendiri.</span>
                        </label>
                        <label class="panduan-check flex items-start gap-2 text-sm text-slate-600">
                            <input type="checkbox" class="mt-1 rounded border-slate-300">
                            <span>Jadwal hari ini tersedia untuk kelas.</span>
                        </label>
                        <label class="panduan-check flex items-start gap-2 text-sm text-slate-600">
                            <input type="checkbox" class="mt-1 rounded border-slate-300">
                            <span>Siswa sudah punya minimal 5 foto dataset.</span>
                        </label>
                        <label class="panduan-check flex items-start gap-2 text-sm text-slate-600">
                            <input type="checkbox" class="mt-1 rounded border-slate-300">
                            <span>Training CNN sudah selesai.</span>
                        </label>
                        <label class="panduan-check flex items-start gap-2 text-sm text-slate-600">
                            <input type="checkbox" class="mt-1 rounded border-slate-300">
                            <span>Python CNN service aktif di port 5001.</span>
                        </label>
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-100 rounded-lg p-5">
                    <h3 class="text-sm font-bold text-blue-900">Tips dataset bagus</h3>
                    <ul class="text-sm text-blue-900/80 mt-3 space-y-2 leading-6">
                        <li>Ambil foto di tempat terang, wajah menghadap kamera.</li>
                        <li>Jangan terlalu jauh dari kamera.</li>
                        <li>Ambil beberapa variasi kecil: lurus, sedikit kanan, sedikit kiri.</li>
                        <li>Hindari wajah tertutup masker, tangan, atau cahaya belakang terlalu kuat.</li>
                    </ul>
                </div>
            </aside>
        </div>
    </section>

    <section id="panel-role" class="panduan-panel">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div class="bg-white border border-slate-200 rounded-lg p-5">
                <div class="w-9 h-9 rounded-lg bg-blue-50 text-[#1E40AF] flex items-center justify-center mb-4">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3 7 7 .5-5.5 4.5 1.8 7-6.3-3.8L5.7 21l1.8-7L2 9.5 9 9z"/></svg>
                </div>
                <h3 class="text-base font-bold text-slate-900">Admin</h3>
                <p class="text-sm text-slate-500 mt-1">Role pengelola utama sistem.</p>
                <ul class="text-sm text-slate-600 mt-4 space-y-2 leading-6">
                    <li><b>Dashboard:</b> melihat ringkasan kehadiran hari ini.</li>
                    <li><b>Kelola Siswa:</b> tambah, ubah, hapus, dan ambil dataset wajah siswa.</li>
                    <li><b>Kelola Kelas:</b> mengatur kelas, tahun, serta sumber koordinat GPS.</li>
                    <li><b>Jadwal:</b> mengatur mata pelajaran, guru, hari, dan jam belajar.</li>
                    <li><b>Training CNN:</b> melatih ulang model dari dataset terbaru.</li>
                    <li><b>Pengaturan:</b> mengatur titik koordinat sekolah untuk geofencing.</li>
                    <li><b>Absensi Kamera:</b> menjalankan kamera absensi siswa.</li>
                    <li><b>Rekap, Rekap Guru, Laporan:</b> memantau dan mengekspor data.</li>
                    <li><b>RPA Bot:</b> melihat status bot dan menjalankan proses manual.</li>
                    <li><b>Panduan:</b> membaca panduan operasional ini.</li>
                </ul>
            </div>

            <div class="bg-white border border-slate-200 rounded-lg p-5">
                <div class="w-9 h-9 rounded-lg bg-green-50 text-[#15803D] flex items-center justify-center mb-4">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="7" r="4"/><path d="M5.5 21a6.5 6.5 0 0 1 13 0"/></svg>
                </div>
                <h3 class="text-base font-bold text-slate-900">Guru</h3>
                <p class="text-sm text-slate-500 mt-1">Role pengajar dan pelaksana absensi.</p>
                <ul class="text-sm text-slate-600 mt-4 space-y-2 leading-6">
                    <li><b>Dashboard:</b> melihat ringkasan kehadiran.</li>
                    <li><b>Absensi Kamera:</b> menjalankan absensi siswa pada kelas/jadwal.</li>
                    <li><b>Rekap Absensi:</b> melihat rekap absensi siswa.</li>
                    <li><b>Absensi Guru:</b> melakukan absensi kehadiran guru sesuai jadwal sendiri.</li>
                    <li><b>Dataset Wajah:</b> mengambil dataset wajah guru sendiri.</li>
                    <li><b>Rekap Guru:</b> melihat rekap kehadiran guru sendiri.</li>
                    <li><b>Laporan:</b> melihat laporan sesuai akses yang tersedia.</li>
                </ul>
            </div>

            <div class="bg-white border border-slate-200 rounded-lg p-5">
                <div class="w-9 h-9 rounded-lg bg-amber-50 text-[#B45309] flex items-center justify-center mb-4">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 21v-8h6v8"/></svg>
                </div>
                <h3 class="text-base font-bold text-slate-900">Kepala Sekolah</h3>
                <p class="text-sm text-slate-500 mt-1">Role pemantau rekap dan laporan.</p>
                <ul class="text-sm text-slate-600 mt-4 space-y-2 leading-6">
                    <li><b>Dashboard:</b> melihat kondisi umum absensi.</li>
                    <li><b>Rekap Absensi:</b> melihat data absensi siswa berdasarkan filter.</li>
                    <li><b>Rekap Guru:</b> melihat rekap kehadiran guru.</li>
                    <li><b>Laporan:</b> membuka dan mengekspor laporan absensi.</li>
                    <li>Tidak mengelola dataset, siswa, kelas, jadwal, training, atau RPA.</li>
                </ul>
            </div>
        </div>
    </section>

    <section id="panel-cnn" class="panduan-panel space-y-5">
        <div class="bg-white border border-slate-200 rounded-lg p-5">
            <h3 class="text-sm font-bold text-slate-900">Pipeline CNN di aplikasi ini</h3>
            <p class="text-sm text-slate-600 mt-2 leading-6">
                Model yang dipakai adalah MobileNetV2 berbasis CNN dengan transfer learning. Artinya, sistem memakai backbone yang sudah belajar pola visual umum,
                lalu melatih bagian akhir model agar dapat membedakan wajah siswa dan guru dari dataset lokal sekolah.
            </p>

            <div class="grid grid-cols-1 md:grid-cols-5 gap-3 mt-5">
                <div class="rounded-lg border border-slate-200 p-4">
                    <p class="text-xs font-bold text-[#1E40AF]">1. Capture</p>
                    <p class="text-sm text-slate-600 mt-2 leading-5">Kamera browser mengirim gambar JPEG base64 ke PHP.</p>
                </div>
                <div class="rounded-lg border border-slate-200 p-4">
                    <p class="text-xs font-bold text-[#1E40AF]">2. Deteksi</p>
                    <p class="text-sm text-slate-600 mt-2 leading-5">OpenCV Haar Cascade mencari area wajah terbesar.</p>
                </div>
                <div class="rounded-lg border border-slate-200 p-4">
                    <p class="text-xs font-bold text-[#1E40AF]">3. Preprocess</p>
                    <p class="text-sm text-slate-600 mt-2 leading-5">Wajah diberi margin, cahaya dinormalisasi, resize 224 x 224, lalu piksel dibuat 0-1.</p>
                </div>
                <div class="rounded-lg border border-slate-200 p-4">
                    <p class="text-xs font-bold text-[#1E40AF]">4. Prediksi</p>
                    <p class="text-sm text-slate-600 mt-2 leading-5">Flask menjalankan model dan menghitung confidence setiap identitas.</p>
                </div>
                <div class="rounded-lg border border-slate-200 p-4">
                    <p class="text-xs font-bold text-[#1E40AF]">5. Validasi</p>
                    <p class="text-sm text-slate-600 mt-2 leading-5">PHP mencocokkan identitas, kelas/jadwal, GPS, dan duplikasi absensi.</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <div class="bg-white border border-slate-200 rounded-lg p-5">
                <h3 class="text-sm font-bold text-slate-900 mb-3">Saat training</h3>
                <div class="space-y-3 text-sm text-slate-600 leading-6">
                    <p><b>Dataset dibaca:</b> folder di <code class="font-mono text-xs">python/dataset/[NIS atau username]/</code> menjadi label identitas.</p>
                    <p><b>Wajah diproses:</b> setiap foto dicrop wajahnya, resize ke 224 x 224, dan dinormalisasi.</p>
                    <p><b>Augmentasi dibuat:</b> sistem menambah variasi flip, rotasi, zoom, terang/redup, noise, dan blur agar model tidak mudah gagal saat kondisi kamera berubah.</p>
                    <p><b>Model dilatih 2 fase:</b> fase pertama melatih head classifier, fase kedua fine-tune 30 layer terakhir MobileNetV2.</p>
                    <p><b>Output disimpan:</b> model masuk ke <code class="font-mono text-xs">model_absensi.h5</code> dan label indeks ke identitas masuk ke <code class="font-mono text-xs">label_map.json</code>.</p>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-lg p-5">
                <h3 class="text-sm font-bold text-slate-900 mb-3">Saat absensi</h3>
                <div class="space-y-3 text-sm text-slate-600 leading-6">
                    <p><b>PHP mengirim gambar:</b> controller memanggil Flask endpoint <code class="font-mono text-xs">/kenali-wajah</code> lewat <code class="font-mono text-xs">CNNService</code>.</p>
                    <p><b>TTA dipakai:</b> model memprediksi 4 versi gambar, yaitu asli, flip, rotasi +5 derajat, dan rotasi -5 derajat, lalu hasilnya dirata-rata.</p>
                    <p><b>Ambang confidence:</b> confidence minimal 0.85 dianggap berhasil. Nilai 0.70-0.84 diberi pesan coba ulang dengan pencahayaan lebih baik.</p>
                    <p><b>Siswa:</b> hasil prediksi berupa NIS, lalu dicocokkan ke data siswa dan kelas jadwal.</p>
                    <p><b>Guru:</b> hasil prediksi berupa username, lalu harus sama dengan username guru yang sedang login.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="panel-masalah" class="panduan-panel">
        <div class="bg-white border border-slate-200 rounded-lg divide-y divide-slate-100">
            <details class="panduan-detail p-5" open>
                <summary class="cursor-pointer text-sm font-bold text-slate-900">Tombol training tidak bisa diklik</summary>
                <p class="text-sm text-slate-600 mt-3 leading-6">
                    Pastikan ada minimal 2 identitas dengan masing-masing minimal 5 foto. Untuk hasil lebih stabil, lengkapi 10 foto per identitas sebelum training.
                </p>
            </details>
            <details class="panduan-detail p-5">
                <summary class="cursor-pointer text-sm font-bold text-slate-900">CNN service mati atau tidak merespons</summary>
                <p class="text-sm text-slate-600 mt-3 leading-6">
                    Jalankan service dari folder <code class="font-mono text-xs">python/</code> dengan <code class="font-mono text-xs">python app.py</code>,
                    atau jalankan <code class="font-mono text-xs">./start.sh</code>. Aplikasi saat ini memakai endpoint <code class="font-mono text-xs">http://127.0.0.1:5001/status</code>.
                </p>
            </details>
            <details class="panduan-detail p-5">
                <summary class="cursor-pointer text-sm font-bold text-slate-900">Model belum ada</summary>
                <p class="text-sm text-slate-600 mt-3 leading-6">
                    Buka menu <b>Training CNN</b> dan jalankan training. Setelah berhasil, file <code class="font-mono text-xs">model_absensi.h5</code>
                    dan <code class="font-mono text-xs">label_map.json</code> harus ada di folder <code class="font-mono text-xs">python/</code>.
                </p>
            </details>
            <details class="panduan-detail p-5">
                <summary class="cursor-pointer text-sm font-bold text-slate-900">Wajah tidak dikenali</summary>
                <p class="text-sm text-slate-600 mt-3 leading-6">
                    Cek pencahayaan, jarak wajah, posisi wajah, dan kualitas dataset. Jika wajah baru saja ditambahkan atau dataset dihapus lalu diambil ulang,
                    admin perlu menjalankan training ulang.
                </p>
            </details>
            <details class="panduan-detail p-5">
                <summary class="cursor-pointer text-sm font-bold text-slate-900">Absensi ditolak karena GPS</summary>
                <p class="text-sm text-slate-600 mt-3 leading-6">
                    Pastikan browser mengizinkan lokasi, perangkat berada di area kelas, dan koordinat kelas sudah benar. Jika radius terlalu kecil untuk kondisi lapangan,
                    admin dapat menyesuaikan radius di <b>Pengaturan</b> untuk mode sekolah, atau di <b>Kelola Kelas</b> untuk mode koordinat sendiri.
                </p>
            </details>
            <details class="panduan-detail p-5">
                <summary class="cursor-pointer text-sm font-bold text-slate-900">Siswa dikenali tetapi salah kelas</summary>
                <p class="text-sm text-slate-600 mt-3 leading-6">
                    Sistem menolak absensi jika siswa terdaftar di kelas berbeda dari jadwal yang dipilih. Pilih kelas yang sesuai atau perbaiki data kelas siswa di <b>Kelola Siswa</b>.
                </p>
            </details>
            <details class="panduan-detail p-5">
                <summary class="cursor-pointer text-sm font-bold text-slate-900">Guru gagal absen karena wajah pengguna lain</summary>
                <p class="text-sm text-slate-600 mt-3 leading-6">
                    Untuk guru, hasil CNN harus sama dengan username akun yang sedang login. Ambil ulang dataset guru tersebut, pastikan wajah tidak tercampur dengan orang lain, lalu minta admin training ulang.
                </p>
            </details>
        </div>
    </section>
</div>

<script>
(() => {
    const tabs = Array.from(document.querySelectorAll('.panduan-tab'));
    const panels = Array.from(document.querySelectorAll('.panduan-panel'));

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.target;
            tabs.forEach((item) => item.setAttribute('aria-selected', item === tab ? 'true' : 'false'));
            panels.forEach((panel) => panel.classList.toggle('aktif', panel.id === target));
        });
    });
})();
</script>
