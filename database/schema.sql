-- Sistem Absensi CNN — SMK Nusantara Rantepao
-- Buat database terlebih dahulu sebelum import file ini

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

-- Tabel kelas
CREATE TABLE kelas (
    id    INT AUTO_INCREMENT PRIMARY KEY,
    nama  VARCHAR(20) NOT NULL,
    tahun VARCHAR(9)  NOT NULL,
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
    FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON UPDATE CASCADE,
    FOREIGN KEY (guru_id)  REFERENCES pengguna(id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel absensi
CREATE TABLE absensi (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    siswa_id   INT NOT NULL,
    jadwal_id  INT NOT NULL,
    tanggal    DATE NOT NULL,
    jam        TIME NOT NULL,
    status     ENUM('hadir','terlambat','tidak_hadir') NOT NULL DEFAULT 'hadir',
    confidence DECIMAL(5,4),
    FOREIGN KEY (siswa_id)  REFERENCES siswa(id)  ON UPDATE CASCADE,
    FOREIGN KEY (jadwal_id) REFERENCES jadwal(id) ON UPDATE CASCADE,
    UNIQUE KEY uq_absensi (siswa_id, jadwal_id, tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
