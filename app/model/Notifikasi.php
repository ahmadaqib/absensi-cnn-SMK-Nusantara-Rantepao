<?php

class Notifikasi {

    private PDO $db;

    public function __construct() {
        $this->db = koneksiDB();
    }

    public function buat(int $penerimaId, string $pesan, string $tipe = 'ABSEN'): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO notifikasi (penerima_id, pesan, tipe) VALUES (?, ?, ?)"
        );
        return $stmt->execute([$penerimaId, $pesan, $tipe]);
    }

    public function hitungBelumDibaca(int $penerimaId): int {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM notifikasi WHERE penerima_id=? AND dibaca=0"
        );
        $stmt->execute([$penerimaId]);
        return (int) $stmt->fetchColumn();
    }

    public function ambilTerbaru(int $penerimaId, int $limit = 5): array {
        $stmt = $this->db->prepare(
            "SELECT id, pesan, tipe, dibaca, dibuat_pada
             FROM notifikasi
             WHERE penerima_id=?
             ORDER BY dibuat_pada DESC
             LIMIT ?"
        );
        $stmt->execute([$penerimaId, $limit]);
        return $stmt->fetchAll();
    }

    public function tandaiDibaca(int $penerimaId, ?int $id = null): bool {
        if ($id) {
            $stmt = $this->db->prepare(
                "UPDATE notifikasi SET dibaca=1 WHERE id=? AND penerima_id=?"
            );
            return $stmt->execute([$id, $penerimaId]);
        }

        $stmt = $this->db->prepare(
            "UPDATE notifikasi SET dibaca=1 WHERE penerima_id=? AND dibaca=0"
        );
        return $stmt->execute([$penerimaId]);
    }
}
