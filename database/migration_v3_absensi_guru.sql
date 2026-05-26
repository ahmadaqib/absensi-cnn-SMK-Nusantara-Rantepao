USE sistem_absensi;

-- Tabel absensi kehadiran guru (+ koordinat GPS)
CREATE TABLE IF NOT EXISTS absensi_guru (
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
