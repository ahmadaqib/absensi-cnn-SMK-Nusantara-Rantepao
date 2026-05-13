<?php
/**
 * UiPath Workflow: ProsesAbsensi
 * Baca presensi_antrian PENDING → validasi → INSERT ke absensi final.
 */
class ProsesAbsensi {

    private PDO        $db;
    private UiPathBot  $bot;

    public function __construct(PDO $db, UiPathBot $bot) {
        $this->db  = $db;
        $this->bot = $bot;
    }

    public function jalankan(): void {
        $stmt = $this->db->prepare(
            "SELECT * FROM presensi_antrian
             WHERE status = 'PENDING'
             ORDER BY timestamp_masuk ASC
             LIMIT 50"
        );
        $stmt->execute();
        $antrian = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($antrian as $item) {
            $this->prosesRecord($item);
        }

        if (count($antrian) > 0) {
            $this->bot->log("ProsesAbsensi: " . count($antrian) . " record diproses.");
        }
    }

    private function prosesRecord(array $item): void {
        $this->ubahStatus($item['id'], 'PROCESSING');
        try {
            // Cek duplikasi di tabel final
            $stmt = $this->db->prepare(
                "SELECT COUNT(*) FROM absensi
                 WHERE siswa_id=? AND jadwal_id=? AND tanggal=?"
            );
            $stmt->execute([
                $item['siswa_id'],
                $item['jadwal_id'],
                date('Y-m-d', strtotime($item['timestamp_masuk'])),
            ]);
            if ($stmt->fetchColumn() > 0) {
                $this->ubahStatus($item['id'], 'GAGAL', 'Sudah absen di sesi ini.');
                return;
            }

            // Cek jadwal aktif hari ini
            if (!$this->jadwalAktif($item['jadwal_id'])) {
                $this->ubahStatus($item['id'], 'GAGAL', 'Jadwal tidak aktif hari ini.');
                return;
            }

            $status = $this->tentukanStatus($item['jadwal_id'], $item['timestamp_masuk']);

            $this->db->prepare(
                "INSERT INTO absensi
                    (siswa_id, jadwal_id, tanggal, jam, status,
                     confidence, latitude_absensi, longitude_absensi, jarak_dari_kelas)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            )->execute([
                $item['siswa_id'],
                $item['jadwal_id'],
                date('Y-m-d', strtotime($item['timestamp_masuk'])),
                date('H:i:s', strtotime($item['timestamp_masuk'])),
                $status,
                $item['confidence'],
                $item['latitude'],
                $item['longitude'],
                $item['jarak_dari_kelas'],
            ]);

            $this->ubahStatus($item['id'], 'DONE');
        } catch (Throwable $e) {
            $this->ubahStatus($item['id'], 'GAGAL', $e->getMessage());
        }
    }

    private function jadwalAktif(int $jadwalId): bool {
        $hariMap      = [1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',7=>'Minggu'];
        $hariSekarang = $hariMap[(int) date('N')] ?? '';
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM jadwal WHERE id=? AND hari=?");
        $stmt->execute([$jadwalId, $hariSekarang]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function tentukanStatus(int $jadwalId, string $timestamp): string {
        $stmt = $this->db->prepare("SELECT jam_mulai FROM jadwal WHERE id=?");
        $stmt->execute([$jadwalId]);
        $jadwal = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$jadwal) return 'hadir';

        $batasTelat = strtotime(date('Y-m-d') . ' ' . $jadwal['jam_mulai']) + (15 * 60);
        return strtotime($timestamp) <= $batasTelat ? 'hadir' : 'terlambat';
    }

    private function ubahStatus(int $id, string $status, ?string $pesanError = null): void {
        $this->db->prepare(
            "UPDATE presensi_antrian
             SET status=?, pesan_error=?, diproses_pada=NOW()
             WHERE id=?"
        )->execute([$status, $pesanError, $id]);
    }
}
