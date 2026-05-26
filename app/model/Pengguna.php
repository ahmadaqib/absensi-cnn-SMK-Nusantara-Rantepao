<?php

class Pengguna {

    private PDO $db;

    public function __construct() {
        $this->db = koneksiDB();
    }

    public function cariByUsername(string $username): ?array {
        $stmt = $this->db->prepare("SELECT * FROM pengguna WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $data = $stmt->fetch();
        return $data ?: null;
    }

    public function cariById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT id, nama, username, role FROM pengguna WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        return $data ?: null;
    }

    public function ambilSemua(string $role = ''): array {
        if ($role) {
            $stmt = $this->db->prepare("SELECT id, nama, username, role, dibuat_pada FROM pengguna WHERE role = ? ORDER BY nama");
            $stmt->execute([$role]);
        } else {
            $stmt = $this->db->query("SELECT id, nama, username, role, dibuat_pada FROM pengguna ORDER BY nama");
        }
        return $stmt->fetchAll();
    }

    public function simpan(array $data): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO pengguna (nama, username, password, role) VALUES (?, ?, ?, ?)"
        );
        return $stmt->execute([
            $data['nama'],
            $data['username'],
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['role'],
        ]);
    }

    public function update(int $id, array $data): bool {
        // Update password hanya jika diberikan
        if (!empty($data['password'])) {
            $stmt = $this->db->prepare(
                "UPDATE pengguna SET nama=?, username=?, password=?, role=? WHERE id=?"
            );
            return $stmt->execute([
                $data['nama'],
                $data['username'],
                password_hash($data['password'], PASSWORD_BCRYPT),
                $data['role'],
                $id,
            ]);
        }

        $stmt = $this->db->prepare(
            "UPDATE pengguna SET nama=?, username=?, role=? WHERE id=?"
        );
        return $stmt->execute([$data['nama'], $data['username'], $data['role'], $id]);
    }

    public function hapus(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM pengguna WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
