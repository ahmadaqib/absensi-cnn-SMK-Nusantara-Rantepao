<?php

class Pengaturan {

    private PDO $db;

    public function __construct() {
        $this->db = koneksiDB();
        $this->pastikanTabel();
    }

    public function ambil(string $kunci, ?string $default = null): ?string {
        $stmt = $this->db->prepare("SELECT nilai FROM pengaturan WHERE kunci = ? LIMIT 1");
        $stmt->execute([$kunci]);
        $nilai = $stmt->fetchColumn();
        return $nilai === false ? $default : (string) $nilai;
    }

    public function simpan(string $kunci, ?string $nilai): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO pengaturan (kunci, nilai, diperbarui_pada)
             VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE nilai = VALUES(nilai), diperbarui_pada = NOW()"
        );
        return $stmt->execute([$kunci, $nilai]);
    }

    public function koordinatSekolah(): array {
        return [
            'latitude'  => $this->ambil('sekolah_latitude', ''),
            'longitude' => $this->ambil('sekolah_longitude', ''),
            'radius'    => $this->ambil('sekolah_radius', (string) RADIUS_MAKSIMAL),
        ];
    }

    public function simpanKoordinatSekolah(string $latitude, string $longitude, string $radius): void {
        $this->simpan('sekolah_latitude', $latitude);
        $this->simpan('sekolah_longitude', $longitude);
        $this->simpan('sekolah_radius', $radius);
    }

    private function pastikanTabel(): void {
        $this->db->exec(
            "CREATE TABLE IF NOT EXISTS pengaturan (
                kunci VARCHAR(100) PRIMARY KEY,
                nilai TEXT NULL,
                diperbarui_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }
}
