<?php

class Kelas {

    private PDO $db;

    public function __construct() {
        $this->db = koneksiDB();
        $this->pastikanKolomSumberKoordinat();
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
        $stmt = $this->db->prepare(
            "INSERT INTO kelas (nama, tahun, sumber_koordinat, latitude, longitude, radius)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $sumber = $data['sumber_koordinat'] ?? 'sekolah';
        return $stmt->execute([
            $data['nama'],
            $data['tahun'],
            $sumber,
            $sumber === 'kelas' && $data['latitude'] !== ''  ? (float) $data['latitude']  : null,
            $sumber === 'kelas' && $data['longitude'] !== '' ? (float) $data['longitude'] : null,
            $sumber === 'kelas' && isset($data['radius']) && $data['radius'] !== '' ? (int) $data['radius'] : null,
        ]);
    }

    public function update(int $id, array $data): bool {
        $stmt = $this->db->prepare(
            "UPDATE kelas SET nama=?, tahun=?, sumber_koordinat=?, latitude=?, longitude=?, radius=? WHERE id=?"
        );
        $sumber = $data['sumber_koordinat'] ?? 'sekolah';
        return $stmt->execute([
            $data['nama'],
            $data['tahun'],
            $sumber,
            $sumber === 'kelas' && $data['latitude'] !== ''  ? (float) $data['latitude']  : null,
            $sumber === 'kelas' && $data['longitude'] !== '' ? (float) $data['longitude'] : null,
            $sumber === 'kelas' && isset($data['radius']) && $data['radius'] !== '' ? (int) $data['radius'] : null,
            $id,
        ]);
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

    // Ambil koordinat efektif untuk validasi geofencing.
    // Mode "sekolah" memakai titik sekolah; mode "kelas" memakai titik kelas sendiri.
    public function ambilKoordinat(int $kelasId): ?array {
        $stmt = $this->db->prepare("SELECT sumber_koordinat, latitude, longitude, radius FROM kelas WHERE id=? LIMIT 1");
        $stmt->execute([$kelasId]);
        $data = $stmt->fetch();
        if (!$data) return null;

        $sumber = $data['sumber_koordinat'] ?: 'sekolah';
        if ($sumber === 'kelas') {
            return [
                'sumber'    => 'kelas',
                'latitude'  => $data['latitude'],
                'longitude' => $data['longitude'],
                'radius'    => $data['radius'] ?? RADIUS_MAKSIMAL,
            ];
        }

        $sekolah = (new Pengaturan())->koordinatSekolah();
        return [
            'sumber'    => 'sekolah',
            'latitude'  => $sekolah['latitude'],
            'longitude' => $sekolah['longitude'],
            'radius'    => $sekolah['radius'] ?: RADIUS_MAKSIMAL,
        ];
    }

    private function pastikanKolomSumberKoordinat(): void {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'kelas'
               AND COLUMN_NAME = 'sumber_koordinat'"
        );
        $stmt->execute();
        if ((int) $stmt->fetchColumn() > 0) {
            return;
        }

        $this->db->exec(
            "ALTER TABLE kelas
             ADD COLUMN sumber_koordinat ENUM('sekolah','kelas') NOT NULL DEFAULT 'sekolah' AFTER tahun"
        );
        $this->db->exec(
            "UPDATE kelas
             SET sumber_koordinat = 'kelas'
             WHERE latitude IS NOT NULL AND longitude IS NOT NULL"
        );
    }
}
