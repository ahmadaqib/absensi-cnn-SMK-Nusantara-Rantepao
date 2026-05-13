# Sistem Absensi Wajah Berbasis CNN

Sistem absensi berbasis web yang menggunakan Convolutional Neural Network (CNN) untuk mengenali wajah siswa secara otomatis. Dibangun sebagai proyek skripsi S1 untuk SMK Nusantara Rantepao.

---

## Teknologi

| Layer | Stack |
|---|---|
| Web | PHP 8.x Native + MySQL 8.x |
| Tampilan | Tailwind CSS (CDN) + Inter Font |
| CNN Service | Python 3.11 + TensorFlow + OpenCV |
| API | Flask 2.x (port 5000) |
| Web Server | PHP Built-in Server (`php -S`) |

---

## Fitur

- **Login** per role — Admin, Guru, Kepala Sekolah
- **Kelola Siswa** — CRUD + capture dataset wajah via kamera browser
- **Kelola Kelas & Jadwal** — manajemen data master
- **Training CNN** — trigger training dari web, progress bar real-time
- **Absensi Kamera** — pengenalan wajah otomatis, threshold confidence 85%
- **Rekap & Laporan** — filter per kelas/tanggal, export PDF & Excel
- **Dashboard** — statistik kehadiran hari ini + grafik per kelas

---

## Cara Menjalankan

Pastikan **MySQL XAMPP sudah Running**, lalu:

```bash
# macOS
./start.sh

# Windows
start.bat
```

Buka browser → **http://localhost:8000**

Login default: `admin` / `password`

> Lihat [TUTORIAL.md](TUTORIAL.md) untuk panduan instalasi lengkap dari awal.

---

## Struktur Proyek

```
absensi-cnn/
├── app/
│   ├── controller/     # AuthController, SiswaController, dst.
│   ├── model/          # Pengguna, Siswa, Kelas, Jadwal, Absensi
│   ├── service/        # CNNService.php (wrapper cURL ke Flask)
│   └── helper/         # Auth, Response, Validator
├── views/              # Template PHP per modul
├── python/
│   ├── app.py          # Flask CNN service
│   ├── kenali_wajah.py # Deteksi + inferensi wajah
│   ├── latih_model.py  # Script training CNN
│   ├── preprocessing.py
│   └── dataset/        # Foto wajah per NIS siswa
├── config/             # database.php, app.php
├── database/           # schema.sql, seeder.sql
├── public/             # CSS, JS, gambar upload
├── start.sh            # Jalankan semua (macOS)
├── start.bat           # Jalankan semua (Windows)
└── router.php          # Router PHP built-in server
```

---

## Arsitektur

```
Browser  ──HTTP──▶  PHP (port 8000)  ──cURL──▶  Flask CNN (port 5000)
                         │                              │
                         ▼                              ▼
                      MySQL                     model_absensi.h5
```

Alur absensi: browser capture frame tiap 2 detik → PHP → Flask → CNN inferensi → simpan ke MySQL → notifikasi di browser.

---

## Akurasi Target

| Kondisi | Target |
|---|---|
| Pencahayaan normal | ≥ 90% |
| Wajah miring ≤15° | ≥ 85% |
| Menggunakan kacamata | ≥ 80% |
| Waktu deteksi end-to-end | < 3 detik |

---

## Dokumen

| File | Isi |
|---|---|
| [TUTORIAL.md](TUTORIAL.md) | Panduan instalasi Windows & macOS |
| [resume.md](resume.md) | Progress pengerjaan (sudah & belum) |
| [PRD_Sistem_Absensi_CNN.md](PRD_Sistem_Absensi_CNN.md) | Product Requirements Document lengkap |
