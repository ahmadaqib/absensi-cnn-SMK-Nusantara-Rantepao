# Tutorial Instalasi & Menjalankan Sistem Absensi CNN
### SMK Nusantara Rantepao

**Bahasa:** Indonesia  
**Target:** Windows 10/11 dan macOS 12+  
**Estimasi waktu:** 20–30 menit

---

## Cara Menjalankan (Ringkasan)

Setelah instalasi selesai, cukup:

**Windows** — double-click file `start.bat`  
**macOS** — jalankan `./start.sh` di Terminal

Sistem otomatis membuka web server PHP dan Python CNN service sekaligus.  
Buka browser → **http://localhost:8000**

---

## Daftar Isi

1. [Yang Perlu Diinstall](#1-yang-perlu-diinstall)
2. [Install XAMPP (MySQL saja)](#2-install-xampp-mysql-saja)
3. [Install PHP](#3-install-php)
4. [Install Python](#4-install-python)
5. [Menyiapkan File Proyek](#5-menyiapkan-file-proyek)
6. [Setup Database](#6-setup-database)
7. [Install Dependensi Python](#7-install-dependensi-python)
8. [Menjalankan Sistem](#8-menjalankan-sistem)
9. [Penggunaan Pertama Kali](#9-penggunaan-pertama-kali)
10. [Troubleshooting](#10-troubleshooting)

---

## 1. Yang Perlu Diinstall

| Software | Fungsi | Link Unduh |
|---|---|---|
| **XAMPP** | MySQL database | https://www.apachefriends.org |
| **PHP 8.x** | Web server (sudah termasuk di XAMPP) | — |
| **Python 3.11** | CNN service | https://www.python.org/downloads |

> Apache dari XAMPP **tidak dipakai**. Sistem ini menggunakan PHP built-in server (`php -S`) yang lebih simpel. XAMPP hanya diperlukan untuk MySQL-nya saja.

---

## 2. Install XAMPP (MySQL saja)

### Windows

1. Unduh XAMPP dari https://www.apachefriends.org
2. Jalankan installer sebagai Administrator
3. Saat memilih komponen, minimal centang **MySQL** saja (Apache boleh juga, tidak masalah)
4. Lokasi default: `C:\xampp` — biarkan saja
5. Selesaikan instalasi
6. Buka **XAMPP Control Panel** dari Start Menu
7. Klik **Start** hanya pada baris **MySQL**
8. Pastikan MySQL berstatus hijau (Running)

### macOS

1. Unduh XAMPP untuk macOS dari https://www.apachefriends.org
2. Buka file `.dmg` → seret XAMPP ke Applications
3. Buka **Applications → XAMPP → manager-osx**
4. Klik tab **Manage Servers**
5. Pilih **MySQL Database** → klik **Start**
6. Pastikan MySQL berstatus hijau

---

## 3. Install PHP

PHP sudah termasuk di dalam XAMPP. Yang perlu dilakukan adalah menambahkan PHP ke PATH agar bisa dipanggil dari terminal.

### Windows

1. Buka **System Properties** → **Advanced** → **Environment Variables**
2. Di bagian **System Variables**, cari `Path` → klik **Edit**
3. Klik **New** → tambahkan path: `C:\xampp\php`
4. Klik OK semua
5. Buka Command Prompt baru, ketik:
   ```
   php --version
   ```
   Harus muncul: `PHP 8.x.x ...`

### macOS

PHP dari XAMPP ada di `/Applications/XAMPP/bin/php`. Tambahkan ke PATH:

1. Buka Terminal
2. Jalankan perintah ini (pilih sesuai shell yang digunakan):

   **Jika menggunakan zsh (default macOS Catalina ke atas):**
   ```bash
   echo 'export PATH="/Applications/XAMPP/bin:$PATH"' >> ~/.zshrc
   source ~/.zshrc
   ```

   **Jika menggunakan bash:**
   ```bash
   echo 'export PATH="/Applications/XAMPP/bin:$PATH"' >> ~/.bash_profile
   source ~/.bash_profile
   ```

3. Verifikasi:
   ```bash
   php --version
   ```
   Harus muncul: `PHP 8.x.x ...`

> **Alternatif macOS:** Install PHP via Homebrew jika lebih nyaman:
> ```bash
> brew install php
> ```

---

## 4. Install Python

### Windows

1. Unduh **Python 3.11** dari https://www.python.org/downloads/windows
2. Jalankan installer
3. **PENTING:** Centang kotak **"Add Python to PATH"** sebelum klik Install Now
4. Klik **Install Now**
5. Verifikasi — buka Command Prompt baru:
   ```
   python --version
   ```
   Harus muncul: `Python 3.11.x`

### macOS

1. Unduh **Python 3.11** dari https://www.python.org/downloads/macos
2. Buka file `.pkg` → ikuti langkah instalasi
3. Verifikasi di Terminal:
   ```bash
   python3 --version
   ```
   Harus muncul: `Python 3.11.x`

> **Pengguna macOS chip Apple Silicon (M1/M2/M3):** Python 3.11 sudah mendukung ARM. Tidak perlu langkah khusus.

---

## 5. Menyiapkan File Proyek

Letakkan folder proyek di **mana saja** — tidak harus di dalam htdocs XAMPP karena kita tidak menggunakan Apache.

### Rekomendasi lokasi:

**Windows:** `C:\Projects\absensi-cnn\`  
**macOS:** `~/Projects/absensi-cnn/`

Salin semua file proyek sehingga strukturnya seperti ini:
```
absensi-cnn/
├── start.bat         ← jalankan ini di Windows
├── start.sh          ← jalankan ini di macOS
├── router.php
├── index.php
├── config/
├── app/
├── views/
├── python/
├── database/
└── public/
```

---

## 6. Setup Database

### Buka phpMyAdmin

Buka browser → akses: **http://localhost/phpmyadmin**

(phpMyAdmin ikut terinstal bersama XAMPP)

### Buat Database Baru

1. Klik **"New"** di panel kiri
2. Isi nama: `sistem_absensi`
3. Pilih collation: `utf8mb4_unicode_ci`
4. Klik **Create**

### Import Skema dan Data

1. Klik database `sistem_absensi` di panel kiri
2. Klik tab **Import**
3. Klik **Choose File** → pilih `database/schema.sql`
4. Klik **Go**
5. Ulangi untuk `database/seeder.sql`

Setelah selesai, pastikan ada 5 tabel: `pengguna`, `kelas`, `siswa`, `jadwal`, `absensi`.

---

## 7. Install Dependensi Python

Buka terminal/command prompt, navigasi ke folder `python/` di dalam proyek:

### Windows
```cmd
cd C:\Projects\absensi-cnn\python
python -m venv .venv
.venv\Scripts\activate
python -m pip install -r requirements.txt
```

### macOS
```bash
cd ~/Projects/absensi-cnn/python
python3 -m venv .venv
source .venv/bin/activate
python -m pip install -r requirements.txt
```

Tunggu hingga selesai (5–15 menit, TensorFlow cukup besar).

**Verifikasi:**
```bash
python -c "import flask, tensorflow, cv2, numpy, sklearn; print('OK')"
```
Harus muncul: `OK`

> **macOS Apple Silicon (M1/M2/M3):** Jika TensorFlow gagal diinstall, gunakan:
> ```bash
> python -m pip install tensorflow-macos tensorflow-metal
> ```
> (Hapus dulu `tensorflow` biasa jika sudah terinstall: `python -m pip uninstall tensorflow`)

---

## 8. Menjalankan Sistem

### Windows — Double-click `start.bat`

1. Pastikan **MySQL XAMPP sudah Running**
2. Double-click file **`start.bat`** di folder proyek
3. Akan muncul dua jendela Command Prompt:
   - **Jendela CNN Service** — menjalankan Flask di port 5000
   - **Jendela utama** — menjalankan PHP web server di port 8000
4. Buka browser → **http://localhost:8000**

### macOS — Jalankan `start.sh`

1. Pastikan **MySQL XAMPP sudah Running**
2. Buka Terminal
3. Navigasi ke folder proyek:
   ```bash
   cd ~/Projects/absensi-cnn
   ```
4. Jalankan:
   ```bash
   ./start.sh
   ```
5. Tunggu hingga muncul pesan `Sistem siap!`
6. Buka browser → **http://localhost:8000**
7. Tekan **Ctrl+C** di Terminal untuk menghentikan semua proses sekaligus

### Yang Terjadi Saat start.bat / start.sh Dijalankan

```
start.bat / start.sh
├── [1] Cek PHP, Python, MySQL tersedia
├── [2] Jalankan: python app.py  →  Flask di port 5000
└── [3] Jalankan: php -S localhost:8000 router.php  →  Web di port 8000
```

### Akun Default

| Username | Password | Role |
|---|---|---|
| `admin` | `password` | Administrator |
| `budi` | `password` | Guru |
| `sari` | `password` | Guru |
| `kepsek` | `password` | Kepala Sekolah |

---

## 9. Penggunaan Pertama Kali

Ikuti urutan ini agar absensi kamera bisa berfungsi.

### A — Tambah Data Siswa

1. Login sebagai `admin`
2. Sidebar → **Kelola Siswa** → **+ Tambah Siswa**
3. Isi nama, NIS, pilih kelas → **Tambah Siswa**

### B — Capture Dataset Wajah

1. Di tabel siswa, klik tombol **Dataset** di baris siswa
2. Minta siswa duduk di depan kamera
3. Klik **Ambil Foto** sebanyak 10 kali
   - Variasikan: frontal, sedikit ke kiri, sedikit ke kanan
   - Pastikan pencahayaan ruangan cukup
4. Ulangi untuk semua siswa (minimal 2 siswa)

### C — Training Model CNN

1. Sidebar → **Training CNN**
2. Pastikan info dataset menampilkan siswa berstatus hijau (≥5 foto)
3. Klik **Mulai Training**
4. Tunggu hingga progress bar 100% dan nilai akurasi muncul

### D — Absensi Kamera

1. Sidebar → **Rekap Absensi**
2. Pilih kelas dan jadwal
3. Arahkan wajah siswa ke kamera — sistem otomatis mengenali dan mencatat

---

## 10. Troubleshooting

### `php` tidak dikenali di Command Prompt / Terminal

**Windows:** Tambahkan `C:\xampp\php` ke PATH (lihat langkah 3).  
**macOS:** Tambahkan `/Applications/XAMPP/bin` ke PATH (lihat langkah 3).

---

### Port 8000 sudah dipakai aplikasi lain

Edit baris terakhir di `start.bat` atau `start.sh`, ganti angka port:

```bat
php -S localhost:9000 router.php
```

Lalu sesuaikan juga URL di browser menjadi `http://localhost:9000`.

---

### Port 5000 sudah dipakai (macOS sering ada AirPlay Receiver)

Buka `python/app.py`, ganti port di baris terakhir:

```python
app.run(host='127.0.0.1', port=5001, debug=False)
```

Lalu buka `config/app.php`, ganti:

```php
define('CNN_SERVICE_URL', 'http://localhost:5001');
```

---

### Web terbuka tapi error database

- Pastikan MySQL XAMPP sedang **Running**
- Buka http://localhost/phpmyadmin → cek database `sistem_absensi` ada
- Cek `config/database.php` — `DB_PASS` harus kosong `''` untuk XAMPP default

---

### CNN Service tidak aktif (tanda merah di halaman absensi)

- Pastikan `start.bat`/`start.sh` sudah dijalankan
- Coba akses http://localhost:5000/status di browser
  - Jika tidak bisa dibuka → Flask belum jalan, cek jendela "CNN Service"
  - Jika terbuka tapi `model_ada: false` → lakukan Training CNN terlebih dahulu

---

### Kamera tidak mau muncul di browser

- Browser akan meminta izin kamera — klik **Izinkan**
- Jika sudah pernah ditolak: klik ikon gembok di address bar → atur izin kamera ke **Izinkan**
- Pastikan tidak ada aplikasi lain yang sedang menggunakan kamera (Zoom, Teams, FaceTime)

---

### `ModuleNotFoundError` saat Flask start

Library Python belum terinstall. Jalankan ulang:
```
python -m pip install -r requirements.txt
```

---

### macOS: `permission denied` saat jalankan start.sh

```bash
chmod +x start.sh
./start.sh
```

---

*Setiap kali ingin menggunakan sistem: pastikan MySQL jalan, lalu jalankan `start.bat` atau `./start.sh` — selesai.*
