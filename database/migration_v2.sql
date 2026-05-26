-- Migration v2 — PRD v3 additions
-- Jalankan pada database sistem_absensi yang sudah ada (setelah schema.sql v1)

USE sistem_absensi;

-- Kolom GPS di tabel kelas
ALTER TABLE kelas
    ADD COLUMN latitude  DECIMAL(10, 8) NULL    COMMENT 'Koordinat latitude ruang kelas',
    ADD COLUMN longitude DECIMAL(11, 8) NULL    COMMENT 'Koordinat longitude ruang kelas',
    ADD COLUMN radius    INT DEFAULT 50         COMMENT 'Radius geofencing dalam meter';

-- Kolom GPS + jarak di tabel absensi
ALTER TABLE absensi
    ADD COLUMN latitude_absensi  DECIMAL(10, 8) NULL COMMENT 'Koordinat siswa saat absensi',
    ADD COLUMN longitude_absensi DECIMAL(11, 8) NULL COMMENT 'Koordinat siswa saat absensi',
    ADD COLUMN jarak_dari_kelas  DECIMAL(8, 2)  NULL COMMENT 'Jarak siswa dari kelas dalam meter';

-- Antrian RPA: PHP tulis, UiPath Bot baca dan proses
CREATE TABLE IF NOT EXISTS presensi_antrian (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id         INT NOT NULL,
    jadwal_id        INT NOT NULL,
    timestamp_masuk  DATETIME NOT NULL,
    confidence       DECIMAL(5, 4) NOT NULL,
    latitude         DECIMAL(10, 8) NULL,
    longitude        DECIMAL(11, 8) NULL,
    jarak_dari_kelas DECIMAL(8, 2)  NULL,
    status           ENUM('PENDING','PROCESSING','DONE','GAGAL') DEFAULT 'PENDING',
    pesan_error      VARCHAR(255) NULL,
    diproses_pada    DATETIME NULL,
    FOREIGN KEY (siswa_id)  REFERENCES siswa(id)  ON UPDATE CASCADE,
    FOREIGN KEY (jadwal_id) REFERENCES jadwal(id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifikasi in-app (untuk guru)
CREATE TABLE IF NOT EXISTS notifikasi (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    penerima_id INT NOT NULL,
    pesan       VARCHAR(500) NOT NULL,
    tipe        ENUM('ABSEN','SISTEM','INFO') DEFAULT 'ABSEN',
    dibaca      TINYINT(1) DEFAULT 0,
    dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (penerima_id) REFERENCES pengguna(id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Log laporan yang sudah di-generate oleh UiPath Bot
CREATE TABLE IF NOT EXISTS laporan_tersedia (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    tipe        ENUM('harian','mingguan','bulanan') NOT NULL,
    periode     VARCHAR(50) NOT NULL COMMENT 'contoh: 2026-05-13 atau 2026-05',
    path_file   VARCHAR(255) NOT NULL,
    format      ENUM('pdf','excel') NOT NULL,
    dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Mencegah notifikasi dikirim dua kali per jadwal per hari
CREATE TABLE IF NOT EXISTS notifikasi_terkirim (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    jadwal_id INT NOT NULL,
    tanggal   DATE NOT NULL,
    UNIQUE KEY uq_notif (jadwal_id, tanggal),
    FOREIGN KEY (jadwal_id) REFERENCES jadwal(id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
