<?php

class Jadwal {

    private PDO $db;

    public function __construct() {
        $this->db = koneksiDB();
    }

    public function ambilSemua(): array {
        return $this->db->query(
            "SELECT j.*, k.nama AS nama_kelas, p.nama AS nama_guru
             FROM jadwal j
             JOIN kelas k ON j.kelas_id = k.id
             JOIN pengguna p ON j.guru_id = p.id
             ORDER BY j.hari, j.jam_mulai"
        )->fetchAll();
    }

    public function ambilByKelas(int $kelasId): array {
        $stmt = $this->db->prepare(
            "SELECT j.*, p.nama AS nama_guru
             FROM jadwal j JOIN pengguna p ON j.guru_id = p.id
             WHERE j.kelas_id = ?
             ORDER BY j.hari, j.jam_mulai"
        );
        $stmt->execute([$kelasId]);
        return $stmt->fetchAll();
    }

    public function ambilHariIni(int $kelasId): array {
        $hari = $this->namaHariIndonesia();
        $stmt = $this->db->prepare(
            "SELECT j.*, p.nama AS nama_guru
             FROM jadwal j JOIN pengguna p ON j.guru_id = p.id
             WHERE j.kelas_id = ? AND j.hari = ?
             ORDER BY j.jam_mulai"
        );
        $stmt->execute([$kelasId, $hari]);
        return $stmt->fetchAll();
    }

    public function cariById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM jadwal WHERE id=? LIMIT 1");
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        return $data ?: null;
    }

    public function simpan(array $data): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO jadwal (kelas_id, guru_id, mata_pelajaran, hari, jam_mulai, jam_selesai)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([
            $data['kelas_id'], $data['guru_id'], $data['mata_pelajaran'],
            $data['hari'], $data['jam_mulai'], $data['jam_selesai'],
        ]);
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare(
            "UPDATE jadwal SET kelas_id=?, guru_id=?, mata_pelajaran=?, hari=?, jam_mulai=?, jam_selesai=?
             WHERE id=?"
        );
        return $stmt->execute([
            $data['kelas_id'], $data['guru_id'], $data['mata_pelajaran'],
            $data['hari'], $data['jam_mulai'], $data['jam_selesai'], $id,
        ]);
    }

    public function hapus(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM jadwal WHERE id=?");
        return $stmt->execute([$id]);
    }

    private function namaHariIndonesia(): string {
        $peta = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa',
                 'Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
        return $peta[date('l')] ?? '';
    }
}
