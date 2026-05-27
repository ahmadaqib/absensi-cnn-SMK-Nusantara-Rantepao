<?php

class Siswa {

    private PDO $db;

    public function __construct() {
        $this->db = koneksiDB();
    }

    public function ambilSemua(?int $kelasId = null): array {
        if ($kelasId) {
            $stmt = $this->db->prepare(
                "SELECT s.*, k.nama AS nama_kelas
                 FROM siswa s JOIN kelas k ON s.kelas_id = k.id
                 WHERE s.kelas_id = ? ORDER BY s.nama"
            );
            $stmt->execute([$kelasId]);
        } else {
            $stmt = $this->db->query(
                "SELECT s.*, k.nama AS nama_kelas
                 FROM siswa s JOIN kelas k ON s.kelas_id = k.id
                 ORDER BY k.nama, s.nama"
            );
        }
        return $stmt->fetchAll();
    }

    public function cariById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT s.*, k.nama AS nama_kelas
             FROM siswa s JOIN kelas k ON s.kelas_id = k.id
             WHERE s.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        return $data ?: null;
    }

    public function cariByNis(string $nis): ?array {
        $stmt = $this->db->prepare(
            "SELECT s.*, k.nama AS nama_kelas
             FROM siswa s
             JOIN kelas k ON s.kelas_id = k.id
             WHERE s.nis = ? LIMIT 1"
        );
        $stmt->execute([$nis]);
        $data = $stmt->fetch();
        return $data ?: null;
    }

    public function simpan(array $data): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO siswa (nama, nis, kelas_id, foto, aktif) VALUES (?, ?, ?, ?, 1)"
        );
        return $stmt->execute([$data['nama'], $data['nis'], $data['kelas_id'], $data['foto'] ?? null]);
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare(
            "UPDATE siswa SET nama=?, nis=?, kelas_id=?, aktif=?
             " . (!empty($data['foto']) ? ", foto=?" : "") . "
             WHERE id=?"
        );
        $params = [$data['nama'], $data['nis'], $data['kelas_id'], $data['aktif'] ?? 1];
        if (!empty($data['foto'])) $params[] = $data['foto'];
        $params[] = $id;
        return $stmt->execute($params);
    }

    public function hapus(int $id): bool {
        try {
            $this->db->beginTransaction();

            // 1. Hapus data antrian presensi rpa
            $stmt1 = $this->db->prepare("DELETE FROM presensi_antrian WHERE siswa_id = ?");
            $stmt1->execute([$id]);

            // 2. Hapus data absensi final
            $stmt2 = $this->db->prepare("DELETE FROM absensi WHERE siswa_id = ?");
            $stmt2->execute([$id]);

            // 3. Hapus data siswa
            $stmt3 = $this->db->prepare("DELETE FROM siswa WHERE id = ?");
            $res = $stmt3->execute([$id]);

            $this->db->commit();
            return $res;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function hitungPerKelas(int $kelasId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM siswa WHERE kelas_id=? AND aktif=1");
        $stmt->execute([$kelasId]);
        return (int) $stmt->fetchColumn();
    }

    public function totalAktif(): int {
        return (int) $this->db->query("SELECT COUNT(*) FROM siswa WHERE aktif=1")->fetchColumn();
    }
}
