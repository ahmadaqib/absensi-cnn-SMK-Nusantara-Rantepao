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

    public function cariByUsernameSelainId(string $username, int $id): ?array {
        $stmt = $this->db->prepare("SELECT id, nama, username, role FROM pengguna WHERE username = ? AND id <> ? LIMIT 1");
        $stmt->execute([$username, $id]);
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

    public function ambilGuruDenganStatistik(): array {
        $stmt = $this->db->query(
            "SELECT p.id, p.nama, p.username, p.role, p.dibuat_pada,
                    COUNT(DISTINCT j.id) AS jumlah_jadwal,
                    COUNT(DISTINCT ag.id) AS jumlah_absensi
             FROM pengguna p
             LEFT JOIN jadwal j ON j.guru_id = p.id
             LEFT JOIN absensi_guru ag ON ag.guru_id = p.id
             WHERE p.role = 'guru'
             GROUP BY p.id, p.nama, p.username, p.role, p.dibuat_pada
             ORDER BY p.nama"
        );
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

    public function hapusGuruBesertaRelasi(int $id): bool {
        try {
            $this->db->beginTransaction();

            $stmtAbsensiGuru = $this->db->prepare(
                "DELETE FROM absensi_guru
                 WHERE guru_id = ?
                    OR jadwal_id IN (SELECT id FROM jadwal WHERE guru_id = ?)"
            );
            $stmtAbsensiGuru->execute([$id, $id]);

            $stmtAbsensiSiswa = $this->db->prepare(
                "DELETE FROM absensi
                 WHERE jadwal_id IN (SELECT id FROM jadwal WHERE guru_id = ?)"
            );
            $stmtAbsensiSiswa->execute([$id]);

            $stmtAntrian = $this->db->prepare(
                "DELETE FROM presensi_antrian
                 WHERE jadwal_id IN (SELECT id FROM jadwal WHERE guru_id = ?)"
            );
            $stmtAntrian->execute([$id]);

            $stmtNotifJadwal = $this->db->prepare(
                "DELETE FROM notifikasi_terkirim
                 WHERE jadwal_id IN (SELECT id FROM jadwal WHERE guru_id = ?)"
            );
            $stmtNotifJadwal->execute([$id]);

            $stmtJadwal = $this->db->prepare("DELETE FROM jadwal WHERE guru_id = ?");
            $stmtJadwal->execute([$id]);

            $stmtNotifikasi = $this->db->prepare("DELETE FROM notifikasi WHERE penerima_id = ?");
            $stmtNotifikasi->execute([$id]);

            $stmtGuru = $this->db->prepare("DELETE FROM pengguna WHERE id = ? AND role = 'guru'");
            $hasil = $stmtGuru->execute([$id]);

            $this->db->commit();
            return $hasil;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
