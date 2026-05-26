<?php

/**
 * UiPath Workflow: KirimNotifikasi
 * Tutup sesi jadwal yang sudah selesai, tandai siswa absen, lalu kirim notifikasi ke guru.
 */
class KirimNotifikasi {

    private PDO       $db;
    private UiPathBot $bot;
    private TelegramBot $telegram;

    public function __construct(PDO $db, UiPathBot $bot) {
        $this->db       = $db;
        $this->bot      = $bot;
        $this->telegram = new TelegramBot();
    }

    public function cekSesiSelesai(): void {
        $hariMap = [1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',7=>'Minggu'];
        $hari    = $hariMap[(int) date('N')] ?? '';
        $tanggal = date('Y-m-d');

        $stmt = $this->db->prepare(
            "SELECT j.*, k.nama AS nama_kelas, p.nama AS nama_guru
             FROM jadwal j
             JOIN kelas k ON k.id = j.kelas_id
             JOIN pengguna p ON p.id = j.guru_id
             LEFT JOIN notifikasi_terkirim nt
                ON nt.jadwal_id = j.id AND nt.tanggal = ?
             WHERE j.hari = ?
               AND j.jam_selesai <= CURTIME()
               AND nt.id IS NULL
             ORDER BY j.jam_selesai ASC"
        );
        $stmt->execute([$tanggal, $hari]);
        $jadwalSelesai = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($jadwalSelesai as $jadwal) {
            $this->prosesJadwal($jadwal, $tanggal);
        }
    }

    private function prosesJadwal(array $jadwal, string $tanggal): void {
        $this->db->beginTransaction();
        $pesanTelegram = null;

        try {
            $siswaBelumAbsen = $this->ambilSiswaBelumAbsen((int) $jadwal['id'], (int) $jadwal['kelas_id'], $tanggal);
            foreach ($siswaBelumAbsen as $siswa) {
                $this->tandaiTidakHadir((int) $siswa['id'], (int) $jadwal['id'], $tanggal, $jadwal['jam_selesai']);
            }

            if (count($siswaBelumAbsen) > 0) {
                $contohNama = implode(', ', array_slice(array_column($siswaBelumAbsen, 'nama'), 0, 3));
                $sisa       = count($siswaBelumAbsen) > 3 ? ' dan lainnya' : '';
                $pesan      = sprintf(
                    '%d siswa tidak hadir pada %s kelas %s (%s): %s%s.',
                    count($siswaBelumAbsen),
                    $jadwal['mata_pelajaran'],
                    $jadwal['nama_kelas'],
                    substr($jadwal['jam_mulai'], 0, 5) . '-' . substr($jadwal['jam_selesai'], 0, 5),
                    $contohNama,
                    $sisa
                );
                $this->db->prepare(
                    "INSERT INTO notifikasi (penerima_id, pesan, tipe) VALUES (?, ?, 'ABSEN')"
                )->execute([(int) $jadwal['guru_id'], $pesan]);

                $pesanTelegram = $this->formatPesanTelegram($jadwal, $tanggal, $siswaBelumAbsen);
            }

            $this->db->prepare(
                "INSERT INTO notifikasi_terkirim (jadwal_id, tanggal) VALUES (?, ?)"
            )->execute([(int) $jadwal['id'], $tanggal]);

            $this->db->commit();
            $this->bot->log("KirimNotifikasi: jadwal {$jadwal['id']} ditutup, " . count($siswaBelumAbsen) . " siswa tidak hadir.");
            if ($pesanTelegram !== null) {
                $this->kirimTelegram($pesanTelegram, (int) $jadwal['id']);
            }
        } catch (Throwable $e) {
            $this->db->rollBack();
            $this->bot->log("KirimNotifikasi gagal jadwal {$jadwal['id']}: " . $e->getMessage());
        }
    }

    private function ambilSiswaBelumAbsen(int $jadwalId, int $kelasId, string $tanggal): array {
        $stmt = $this->db->prepare(
            "SELECT s.id, s.nama
             FROM siswa s
             LEFT JOIN absensi a
                ON a.siswa_id = s.id AND a.jadwal_id = ? AND a.tanggal = ?
             WHERE s.kelas_id = ?
               AND s.aktif = 1
               AND a.id IS NULL
             ORDER BY s.nama"
        );
        $stmt->execute([$jadwalId, $tanggal, $kelasId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function tandaiTidakHadir(int $siswaId, int $jadwalId, string $tanggal, string $jamSelesai): void {
        $this->db->prepare(
            "INSERT IGNORE INTO absensi (siswa_id, jadwal_id, tanggal, jam, status)
             VALUES (?, ?, ?, ?, 'tidak_hadir')"
        )->execute([$siswaId, $jadwalId, $tanggal, $jamSelesai]);
    }

    private function formatPesanTelegram(array $jadwal, string $tanggal, array $siswaBelumAbsen): string {
        $daftarNama = array_map(
            fn(array $siswa) => '- ' . TelegramBot::escape($siswa['nama']),
            array_slice($siswaBelumAbsen, 0, 20)
        );

        if (count($siswaBelumAbsen) > 20) {
            $daftarNama[] = '- dan ' . (count($siswaBelumAbsen) - 20) . ' siswa lainnya';
        }

        return "<b>Notifikasi Absensi</b>\n"
            . "Tanggal: " . TelegramBot::escape(date('d/m/Y', strtotime($tanggal))) . "\n"
            . "Guru: " . TelegramBot::escape($jadwal['nama_guru']) . "\n"
            . "Kelas: " . TelegramBot::escape($jadwal['nama_kelas']) . "\n"
            . "Mapel: " . TelegramBot::escape($jadwal['mata_pelajaran']) . "\n"
            . "Jam: " . TelegramBot::escape(substr($jadwal['jam_mulai'], 0, 5) . '-' . substr($jadwal['jam_selesai'], 0, 5)) . "\n\n"
            . "<b>" . count($siswaBelumAbsen) . " siswa tidak hadir:</b>\n"
            . implode("\n", $daftarNama);
    }

    private function kirimTelegram(string $pesan, int $jadwalId): void {
        $hasil = $this->telegram->kirimPesan($pesan);
        if ($hasil['ok']) {
            $this->bot->log("Telegram: notifikasi jadwal $jadwalId terkirim.");
            return;
        }

        $this->bot->log("Telegram gagal jadwal $jadwalId: " . ($hasil['error'] ?? 'error tidak diketahui'));
    }
}
