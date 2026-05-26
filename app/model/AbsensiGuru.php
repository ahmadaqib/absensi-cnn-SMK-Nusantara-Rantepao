<?php

class AbsensiGuru {

    private PDO $db;

    public function __construct() {
        $this->db = koneksiDB();
        $this->pastikanTabel();
    }

    public function simpan(array $data): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO absensi_guru
                (guru_id, jadwal_id, tanggal, jam, status,
                 latitude_absensi, longitude_absensi, jarak_dari_kelas)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                jam=VALUES(jam),
                status=VALUES(status),
                latitude_absensi=VALUES(latitude_absensi),
                longitude_absensi=VALUES(longitude_absensi),
                jarak_dari_kelas=VALUES(jarak_dari_kelas)"
        );

        return $stmt->execute([
            $data['guru_id'],
            $data['jadwal_id'],
            $data['tanggal'],
            $data['jam'],
            $data['status'],
            $data['latitude_absensi'] ?? null,
            $data['longitude_absensi'] ?? null,
            $data['jarak_dari_kelas'] ?? null,
        ]);
    }

    public function sudahAbsen(int $guruId, int $jadwalId, string $tanggal): bool {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM absensi_guru WHERE guru_id=? AND jadwal_id=? AND tanggal=?"
        );
        $stmt->execute([$guruId, $jadwalId, $tanggal]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function ambilHariIniGuru(int $guruId): array {
        $stmt = $this->db->prepare(
            "SELECT ag.*, j.mata_pelajaran, j.jam_mulai, j.jam_selesai, k.nama AS nama_kelas
             FROM absensi_guru ag
             JOIN jadwal j ON ag.jadwal_id = j.id
             JOIN kelas k ON j.kelas_id = k.id
             WHERE ag.guru_id = ? AND ag.tanggal = CURDATE()
             ORDER BY ag.jam DESC"
        );
        $stmt->execute([$guruId]);
        return $stmt->fetchAll();
    }

    public function ambilDenganFilter(array $filter): array {
        $kondisi = ['1=1'];
        $params = [];

        if (!empty($filter['guru_id'])) {
            $kondisi[] = 'ag.guru_id = ?';
            $params[] = $filter['guru_id'];
        }
        if (!empty($filter['tanggal_dari'])) {
            $kondisi[] = 'ag.tanggal >= ?';
            $params[] = $filter['tanggal_dari'];
        }
        if (!empty($filter['tanggal_sampai'])) {
            $kondisi[] = 'ag.tanggal <= ?';
            $params[] = $filter['tanggal_sampai'];
        }
        if (!empty($filter['status'])) {
            $kondisi[] = 'ag.status = ?';
            $params[] = $filter['status'];
        }

        $sql = "SELECT ag.*, p.nama AS nama_guru, p.username,
                       j.mata_pelajaran, j.jam_mulai, j.jam_selesai,
                       k.nama AS nama_kelas
                FROM absensi_guru ag
                JOIN pengguna p ON ag.guru_id = p.id
                JOIN jadwal j ON ag.jadwal_id = j.id
                JOIN kelas k ON j.kelas_id = k.id
                WHERE " . implode(' AND ', $kondisi) . "
                ORDER BY ag.tanggal DESC, ag.jam DESC
                LIMIT 500";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function pastikanTabel(): void {
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS absensi_guru (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }
}
