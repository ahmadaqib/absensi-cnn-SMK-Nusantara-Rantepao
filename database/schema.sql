-- Sistem Absensi CNN — SMK Nusantara Rantepao
-- Schema lengkap v2 (PRD v3) — import file ini untuk instalasi baru

CREATE DATABASE IF NOT EXISTS sistem_absensi
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sistem_absensi;

-- Tabel pengguna (admin, guru, kepala sekolah)
CREATE TABLE pengguna (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nama        VARCHAR(100) NOT NULL,
    username    VARCHAR(50)  UNIQUE NOT NULL,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('admin','guru','kepala_sekolah') NOT NULL,
    dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel kelas (+ koordinat GPS untuk geofencing)
CREATE TABLE kelas (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    nama      VARCHAR(20)    NOT NULL,
    tahun     VARCHAR(9)     NOT NULL,
    latitude  DECIMAL(10, 8) NULL    COMMENT 'Koordinat latitude ruang kelas',
    longitude DECIMAL(11, 8) NULL    COMMENT 'Koordinat longitude ruang kelas',
    radius    INT DEFAULT 50         COMMENT 'Radius geofencing dalam meter',
    UNIQUE KEY uq_kelas (nama, tahun)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel siswa
CREATE TABLE siswa (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    nama     VARCHAR(100) NOT NULL,
    nis      VARCHAR(20)  UNIQUE NOT NULL,
    kelas_id INT NOT NULL,
    foto     VARCHAR(255),
    aktif    TINYINT(1) DEFAULT 1,
    FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel jadwal
CREATE TABLE jadwal (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    kelas_id       INT NOT NULL,
    guru_id        INT NOT NULL,
    mata_pelajaran VARCHAR(100) NOT NULL,
    hari           ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL,
    jam_mulai      TIME NOT NULL,
    jam_selesai    TIME NOT NULL,
    FOREIGN KEY (kelas_id) REFERENCES kelas(id)    ON UPDATE CASCADE,
    FOREIGN KEY (guru_id)  REFERENCES pengguna(id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel absensi final (+ koordinat GPS)
CREATE TABLE absensi (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id            INT NOT NULL,
    jadwal_id           INT NOT NULL,
    tanggal             DATE NOT NULL,
    jam                 TIME NOT NULL,
    status              ENUM('hadir','terlambat','tidak_hadir') NOT NULL DEFAULT 'hadir',
    confidence          DECIMAL(5, 4) NULL,
    latitude_absensi    DECIMAL(10, 8) NULL COMMENT 'Koordinat siswa saat absensi',
    longitude_absensi   DECIMAL(11, 8) NULL COMMENT 'Koordinat siswa saat absensi',
    jarak_dari_kelas    DECIMAL(8, 2)  NULL COMMENT 'Jarak siswa dari kelas dalam meter',
    FOREIGN KEY (siswa_id)  REFERENCES siswa(id)   ON UPDATE CASCADE,
    FOREIGN KEY (jadwal_id) REFERENCES jadwal(id)  ON UPDATE CASCADE,
    UNIQUE KEY uq_absensi (siswa_id, jadwal_id, tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel absensi kehadiran guru (+ koordinat GPS)
CREATE TABLE absensi_guru (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    guru_id             INT NOT NULL,
    jadwal_id           INT NOT NULL,
    tanggal             DATE NOT NULL,
    jam                 TIME NOT NULL,
    status              ENUM('hadir','terlambat') NOT NULL DEFAULT 'hadir',
    latitude_absensi    DECIMAL(10, 8) NULL COMMENT 'Koordinat guru saat absensi',
    longitude_absensi   DECIMAL(11, 8) NULL COMMENT 'Koordinat guru saat absensi',
    jarak_dari_kelas    DECIMAL(8, 2)  NULL COMMENT 'Jarak guru dari kelas dalam meter',
    FOREIGN KEY (guru_id)   REFERENCES pengguna(id) ON UPDATE CASCADE,
    FOREIGN KEY (jadwal_id) REFERENCES jadwal(id)   ON UPDATE CASCADE,
    UNIQUE KEY uq_absensi_guru (guru_id, jadwal_id, tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Antrian RPA: PHP tulis, UiPath Bot baca dan proses
CREATE TABLE presensi_antrian (
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
    FOREIGN KEY (siswa_id)  REFERENCES siswa(id)   ON UPDATE CASCADE,
    FOREIGN KEY (jadwal_id) REFERENCES jadwal(id)  ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifikasi in-app (ketidakhadiran → guru)
CREATE TABLE notifikasi (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    penerima_id INT NOT NULL,
    pesan       VARCHAR(500) NOT NULL,
    tipe        ENUM('ABSEN','SISTEM','INFO') DEFAULT 'ABSEN',
    dibaca      TINYINT(1) DEFAULT 0,
    dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (penerima_id) REFERENCES pengguna(id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Log laporan yang sudah di-generate
CREATE TABLE laporan_tersedia (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    tipe        ENUM('harian','mingguan','bulanan') NOT NULL,
    periode     VARCHAR(50) NOT NULL,
    path_file   VARCHAR(255) NOT NULL,
    format      ENUM('pdf','excel') NOT NULL,
    dibuat_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Mencegah notifikasi dikirim dua kali per jadwal per hari
CREATE TABLE notifikasi_terkirim (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    jadwal_id INT NOT NULL,
    tanggal   DATE NOT NULL,
    UNIQUE KEY uq_notif (jadwal_id, tanggal),
    FOREIGN KEY (jadwal_id) REFERENCES jadwal(id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
