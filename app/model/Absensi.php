<?php

class Absensi {

    private PDO $db;

    public function __construct() {
        $this->db = koneksiDB();
    }

    // Simpan langsung ke tabel absensi final.
    public function simpan(array $data): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO absensi
                (siswa_id, jadwal_id, tanggal, jam, status, confidence,
                 latitude_absensi, longitude_absensi, jarak_dari_kelas)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                jam=VALUES(jam),
                status=VALUES(status),
                confidence=VALUES(confidence),
                latitude_absensi=VALUES(latitude_absensi),
                longitude_absensi=VALUES(longitude_absensi),
                jarak_dari_kelas=VALUES(jarak_dari_kelas)"
        );
        return $stmt->execute([
            $data['siswa_id'],
            $data['jadwal_id'],
            $data['tanggal'],
            $data['jam'],
            $data['status'],
            $data['confidence'] ?? null,
            $data['latitude_absensi']  ?? null,
            $data['longitude_absensi'] ?? null,
            $data['jarak_dari_kelas']  ?? null,
        ]);
    }

    // Tulis jejak ke antrian RPA. Default PENDING, bisa DONE jika sudah disimpan final.
    public function simpanAntrian(array $data): bool {
        $status = $data['status'] ?? 'PENDING';
        if (!in_array($status, ['PENDING', 'PROCESSING', 'DONE', 'GAGAL'], true)) {
            $status = 'PENDING';
        }

        $stmt = $this->db->prepare(
            "INSERT INTO presensi_antrian
                (siswa_id, jadwal_id, timestamp_masuk, confidence,
                 latitude, longitude, jarak_dari_kelas, status, diproses_pada)
             VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([
            $data['siswa_id'],
            $data['jadwal_id'],
            $data['confidence'],
            $data['latitude']          ?? null,
            $data['longitude']         ?? null,
            $data['jarak_dari_kelas']  ?? null,
            $status,
            $status === 'DONE' ? date('Y-m-d H:i:s') : null,
        ]);
    }

    // Hapus entri PENDING di antrian untuk satu siswa (dipanggil saat reset dataset)
    public function hapusAntrianPending(int $siswaId): int {
        $stmt = $this->db->prepare(
            "DELETE FROM presensi_antrian WHERE siswa_id = ? AND status = 'PENDING'"
        );
        $stmt->execute([$siswaId]);
        return (int) $stmt->rowCount();
    }

    public function sudahAbsen(int $siswaId, int $jadwalId, string $tanggal): bool {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM absensi WHERE siswa_id=? AND jadwal_id=? AND tanggal=?"
        );
        $stmt->execute([$siswaId, $jadwalId, $tanggal]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // Cek apakah sudah ada di antrian (belum diproses bot) atau sudah final
    public function sudahDiAntrian(int $siswaId, int $jadwalId): bool {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM presensi_antrian
             WHERE siswa_id=? AND jadwal_id=?
               AND DATE(timestamp_masuk) = CURDATE()
               AND status IN ('PENDING','PROCESSING','DONE')"
        );
        $stmt->execute([$siswaId, $jadwalId]);
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

    // Persentase kehadiran per kelas hari ini (untuk grafik dashboard)
    public function kehadiranPerKelas(): array {
        $tanggal = date('Y-m-d');
        $stmt = $this->db->prepare(
            "SELECT k.nama AS nama_kelas,
                    COUNT(DISTINCT s.id) AS total,
                    COUNT(DISTINCT CASE WHEN a.status IN ('hadir','terlambat') THEN a.siswa_id END) AS hadir
             FROM kelas k
             JOIN siswa s ON s.kelas_id = k.id AND s.aktif = 1
             LEFT JOIN absensi a ON a.siswa_id = s.id AND a.tanggal = ?
             GROUP BY k.id, k.nama
             ORDER BY k.nama"
        );
        $stmt->execute([$tanggal]);
        return $stmt->fetchAll();
    }

    // Absensi hari ini untuk tabel dashboard + live polling
    public function absensiHariIni(int $limit = 20): array {
        $tanggal = date('Y-m-d');
        return $this->ambilDenganFilter([
            'tanggal_dari'   => $tanggal,
            'tanggal_sampai' => $tanggal,
            'limit'          => $limit,
        ]);
    }

    // Untuk laporan — filter fleksibel
    public function ambilDenganFilter(array $filter): array {
        $kondisi = ['1=1'];
        $params  = [];

        if (!empty($filter['kelas_id'])) {
            $kondisi[] = "x.kelas_id = ?";
            $params[]  = $filter['kelas_id'];
        }
        if (!empty($filter['jadwal_id'])) {
            $kondisi[] = "x.jadwal_id = ?";
            $params[]  = $filter['jadwal_id'];
        }
        if (!empty($filter['tanggal_dari'])) {
            $kondisi[] = "x.tanggal >= ?";
            $params[]  = $filter['tanggal_dari'];
        }
        if (!empty($filter['tanggal_sampai'])) {
            $kondisi[] = "x.tanggal <= ?";
            $params[]  = $filter['tanggal_sampai'];
        }
        if (!empty($filter['status'])) {
            $kondisi[] = "x.status = ?";
            $params[]  = $filter['status'];
        }

        $limit = max(1, min((int) ($filter['limit'] ?? 500), 500));
        $sql = "SELECT x.*
                FROM (" . $this->sqlGabunganAbsensi() . ") x
                WHERE " . implode(' AND ', $kondisi) . "
                ORDER BY x.tanggal DESC, x.jam DESC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $i => $value) {
            $stmt->bindValue($i + 1, $value);
        }
        $stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function sqlGabunganAbsensi(): string {
        $toleransiDetik = (int) TOLERANSI_TERLAMBAT * 60;

        return "
            SELECT
                a.id,
                a.siswa_id,
                a.jadwal_id,
                s.kelas_id,
                a.tanggal,
                a.jam,
                a.status,
                a.confidence,
                a.latitude_absensi,
                a.longitude_absensi,
                a.jarak_dari_kelas,
                s.nama AS nama_siswa,
                s.nis,
                k.nama AS nama_kelas,
                j.mata_pelajaran,
                j.hari,
                'FINAL' AS status_antrian,
                NULL AS pesan_error,
                'absensi' AS sumber
            FROM absensi a
            JOIN siswa s  ON a.siswa_id  = s.id
            JOIN kelas k  ON s.kelas_id  = k.id
            JOIN jadwal j ON a.jadwal_id = j.id

            UNION ALL

            SELECT
                q.id,
                q.siswa_id,
                q.jadwal_id,
                s.kelas_id,
                DATE(q.timestamp_masuk) AS tanggal,
                TIME(q.timestamp_masuk) AS jam,
                CASE
                    WHEN TIME(q.timestamp_masuk) > ADDTIME(j.jam_mulai, SEC_TO_TIME($toleransiDetik))
                    THEN 'terlambat'
                    ELSE 'hadir'
                END AS status,
                q.confidence,
                q.latitude AS latitude_absensi,
                q.longitude AS longitude_absensi,
                q.jarak_dari_kelas,
                s.nama AS nama_siswa,
                s.nis,
                k.nama AS nama_kelas,
                j.mata_pelajaran,
                j.hari,
                q.status AS status_antrian,
                q.pesan_error,
                'antrian' AS sumber
            FROM presensi_antrian q
            JOIN siswa s  ON q.siswa_id  = s.id
            JOIN kelas k  ON s.kelas_id  = k.id
            JOIN jadwal j ON q.jadwal_id = j.id
            LEFT JOIN absensi a2
                ON a2.siswa_id = q.siswa_id
               AND a2.jadwal_id = q.jadwal_id
               AND a2.tanggal = DATE(q.timestamp_masuk)
            WHERE a2.id IS NULL
              AND q.status IN ('PENDING', 'PROCESSING', 'DONE')
        ";
    }
}
