# Final Implementation Plan (Sistem Absensi CNN)

Berdasarkan jawaban Anda pada dokumen sebelumnya:
1. **Siswa tidak bisa login.**
2. **Absensi Siswa menggunakan tipe *kiosk/shared camera*.**
3. **Dataset Wajah Guru akan menggunakan `username` sebagai pengenal direktori folder (seperti `python/dataset/budi/`).**

Maka, arsitektur kode saat ini untuk Siswa **SUDAH BENAR** dan sesuai dengan keputusan di atas. Tidak diperlukan pembuatan portal login Siswa. (Catatan: *Use Case Diagram Siswa* yang harus direvisi pada dokumen laporan Anda di luar pengerjaan kode ini).

Fokus implementasi kita sekarang adalah murni pada **Penyelarasan Absensi Guru** agar bisa mendeteksi wajah (CNN).

## Proposed Changes (Rencana Perubahan Kode)

### 1. Manajemen Dataset Wajah Guru
Agar model CNN bisa mengenali wajah Guru, mereka harus mendaftarkan wajah (10 foto) ke dalam sistem (mirip seperti siswa).
- **[NEW] Fungsi Dataset di `app/controller/AbsensiGuruController.php`:**
  Membuat fungsi `dataset()` dan `simpanDataset()` yang bisa diakses oleh Guru untuk mengambil foto wajah mereka sendiri melalui *webcam*.
- **[NEW] `views/absensi_guru/dataset.php`:**
  Antarmuka kamera untuk menangkap dan mengunggah 10 gambar (diadaptasi dari milik Siswa).
- **[MODIFY] API Simpan Foto:**
  Foto akan disimpan ke folder `/python/dataset/[username_guru]/`. Skrip `latih_model.py` (Python) secara otomatis akan membaca nama folder ini sebagai *label* klasifikasi (tidak membedakan antara angka NIS atau huruf Username).

### 2. Modifikasi Kamera Absensi Guru
- **[MODIFY] `views/absensi_guru/index.php`:**
  Menambahkan modul kamera (HTML5 Video & Canvas) ke halaman ini, menggantikan / mendampingi form absen manual.
- **[MODIFY] `app/controller/AbsensiGuruController.php` (Metode `simpan`):**
  Mengubah metode `simpan()` menjadi menerima request AJAX berisi `gambar` (base64) dan `latitude/longitude`. Alurnya:
  1. Terima gambar dan panggil `$this->cnn->kenaliWajah($gambar)`.
  2. Hasil identifikasi (`nis` dari python, yang sekarang juga bisa berisi *username*) akan dicocokkan dengan `username` guru yang sedang login. Jika cocok, absensi dilanjutkan. Jika salah orang (atau wajah tidak dikenali), kembalikan pesan error.
  3. Lanjutkan pemeriksaan validasi jarak GPS (sudah ada di kode saat ini).
  4. Simpan status kehadiran ke database jika sukses.

### 3. Modifikasi Tampilan Sidebar
- **[MODIFY] `views/layouts/sidebar.php`:**
  Tambahkan sub-menu "Dataset Wajah" di bawah menu Absensi Guru jika yang login adalah role `Guru`.

## Verification Plan
1. Login sebagai Guru demo (`budi` / `password`).
2. Masuk ke halaman **Dataset Wajah** (baru) dan rekam 10 foto wajah.
3. Masuk ke menu **Training Model** (di akun Admin) dan latih ulang model agar memasukkan dataset wajah `budi`.
4. Login kembali sebagai `budi`, buka menu **Absensi Guru**.
5. Hadap ke kamera, dan pastikan sistem berhasil mencatat kehadiran dengan wajah yang benar beserta validasi koordinat GPS.
