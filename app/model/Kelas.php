<?php

class Kelas {

    private PDO $db;

    public function __construct() {
        $this->db = koneksiDB();
    }

    public function ambilSemua(): array {
        return $this->db->query("SELECT * FROM kelas ORDER BY tahun DESC, nama ASC")->fetchAll();
    }

    public function cariById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM kelas WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        return $data ?: null;
    }

    public function simpan(array $data): bool {
        $stmt = $this->db->prepare("INSERT INTO kelas (nama, tahun) VALUES (?, ?)");
        return $stmt->execute([$data['nama'], $data['tahun']]);
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare("UPDATE kelas SET nama=?, tahun=? WHERE id=?");
        return $stmt->execute([$data['nama'], $data['tahun'], $id]);
    }

    public function hapus(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM kelas WHERE id=?");
        return $stmt->execute([$id]);
    }

    public function jumlahSiswa(int $kelasId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM siswa WHERE kelas_id=? AND aktif=1");
        $stmt->execute([$kelasId]);
        return (int) $stmt->fetchColumn();
    }
}
