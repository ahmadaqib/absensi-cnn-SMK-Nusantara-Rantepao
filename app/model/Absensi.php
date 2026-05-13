<?php

class Absensi {

    private PDO $db;

    public function __construct() {
        $this->db = koneksiDB();
    }

    public function simpan(array $data): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO absensi (siswa_id, jadwal_id, tanggal, jam, status, confidence)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE jam=VALUES(jam), status=VALUES(status)"
        );
        return $stmt->execute([
            $data['siswa_id'],
            $data['jadwal_id'],
            $data['tanggal'],
            $data['jam'],
            $data['status'],
            $data['confidence'] ?? null,
        ]);
    }

    public function sudahAbsen(int $siswaId, int $jadwalId, string $tanggal): bool {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM absensi WHERE siswa_id=? AND jadwal_id=? AND tanggal=?"
        );
        $stmt->execute([$siswaId, $jadwalId, $tanggal]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // Ringkasan hari ini: jumlah hadir, terlambat, tidak hadir, total terdaftar
    public function ringkasanHariIni(): array {
        $tanggal = date('Y-m-d');
        $stmt = $this->db->prepare(
            "SELECT
                COUNT(DISTINCT CASE WHEN a.status='hadir'       THEN a.siswa_id END) AS hadir,
                COUNT(DISTINCT CASE WHEN a.status='terlambat'   THEN a.siswa_id END) AS terlambat,
                COUNT(DISTINCT CASE WHEN a.status='tidak_hadir' THEN a.siswa_id END) AS tidak_hadir,
                (SELECT COUNT(*) FROM siswa WHERE aktif=1) AS total_siswa
             FROM absensi a
             WHERE a.tanggal = ?"
        );
        $stmt->execute([$tanggal]);
        return $stmt->fetch() ?: ['hadir'=>0,'terlambat'=>0,'tidak_hadir'=>0,'total_siswa'=>0];
    }

    // Persentase kehadiran per kelas hari ini (untuk grafik)
    public function kehadiranPerKelas(): array {
        $tanggal = date('Y-m-d');
        return $this->db->prepare(
            "SELECT k.nama AS nama_kelas,
                    COUNT(DISTINCT s.id) AS total,
                    COUNT(DISTINCT CASE WHEN a.status IN ('hadir','terlambat') THEN a.siswa_id END) AS hadir
             FROM kelas k
             JOIN siswa s ON s.kelas_id = k.id AND s.aktif = 1
             LEFT JOIN absensi a ON a.siswa_id = s.id AND a.tanggal = ?
             GROUP BY k.id, k.nama
             ORDER BY k.nama"
        )->execute([$tanggal])
            ? $this->db->query(
                "SELECT k.nama AS nama_kelas,
                        COUNT(DISTINCT s.id) AS total,
                        COUNT(DISTINCT CASE WHEN a.status IN ('hadir','terlambat') THEN a.siswa_id END) AS hadir
                 FROM kelas k
                 JOIN siswa s ON s.kelas_id = k.id AND s.aktif = 1
                 LEFT JOIN absensi a ON a.siswa_id = s.id AND a.tanggal = '$tanggal'
                 GROUP BY k.id, k.nama ORDER BY k.nama"
            )->fetchAll()
            : [];
    }

    // Absensi hari ini untuk tampil di tabel dashboard
    public function absensiHariIni(int $limit = 20): array {
        $tanggal = date('Y-m-d');
        $stmt = $this->db->prepare(
            "SELECT a.*, s.nama AS nama_siswa, s.nis, k.nama AS nama_kelas,
                    j.mata_pelajaran
             FROM absensi a
             JOIN siswa s  ON a.siswa_id  = s.id
             JOIN kelas k  ON s.kelas_id  = k.id
             JOIN jadwal j ON a.jadwal_id = j.id
             WHERE a.tanggal = ?
             ORDER BY a.jam DESC
             LIMIT ?"
        );
        $stmt->execute([$tanggal, $limit]);
        return $stmt->fetchAll();
    }

    // Untuk laporan — filter fleksibel
    public function ambilDenganFilter(array $filter): array {
        $kondisi = ["1=1"];
        $params  = [];

        if (!empty($filter['kelas_id'])) {
            $kondisi[] = "s.kelas_id = ?";
            $params[]  = $filter['kelas_id'];
        }
        if (!empty($filter['jadwal_id'])) {
            $kondisi[] = "a.jadwal_id = ?";
            $params[]  = $filter['jadwal_id'];
        }
        if (!empty($filter['tanggal_dari'])) {
            $kondisi[] = "a.tanggal >= ?";
            $params[]  = $filter['tanggal_dari'];
        }
        if (!empty($filter['tanggal_sampai'])) {
            $kondisi[] = "a.tanggal <= ?";
            $params[]  = $filter['tanggal_sampai'];
        }
        if (!empty($filter['status'])) {
            $kondisi[] = "a.status = ?";
            $params[]  = $filter['status'];
        }

        $sql = "SELECT a.*, s.nama AS nama_siswa, s.nis,
                       k.nama AS nama_kelas, j.mata_pelajaran, j.hari
                FROM absensi a
                JOIN siswa s  ON a.siswa_id  = s.id
                JOIN kelas k  ON s.kelas_id  = k.id
                JOIN jadwal j ON a.jadwal_id = j.id
                WHERE " . implode(' AND ', $kondisi) . "
                ORDER BY a.tanggal DESC, a.jam DESC
                LIMIT 500";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
