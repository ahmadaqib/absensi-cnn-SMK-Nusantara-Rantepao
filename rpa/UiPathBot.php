<?php
/**
 * UiPathBot — RPA Engine berbasis PHP
 * Nama "UiPath" digunakan sesuai proposal penelitian Risnawati Mangalla'.
 * Implementasi sesungguhnya: PHP script dipanggil Windows Task Scheduler tiap 1 menit.
 */
class UiPathBot {

    private PDO    $db;
    private string $logFile;

    public function __construct(PDO $db) {
        $this->db      = $db;
        $this->logFile = __DIR__ . '/log/uipath.log';
        // Buat direktori log jika belum ada
        if (!is_dir(dirname($this->logFile))) {
            mkdir(dirname($this->logFile), 0755, true);
        }
    }

    // Jalankan semua workflow RPA secara berurutan
    public function jalankan(): void {
        $this->log("UiPath Bot aktif: " . date('Y-m-d H:i:s'));

        try {
            (new ProsesAbsensi($this->db, $this))->jalankan();
            (new KirimNotifikasi($this->db, $this))->cekSesiSelesai();
            (new GenerateLaporan($this->db, $this))->cekDanGenerate();
        } catch (Throwable $e) {
            $this->log("ERROR: " . $e->getMessage());
        }

        $this->log("UiPath Bot selesai.");
    }

    // Statistik untuk ditampilkan di halaman admin
    public function ambilStatistik(): array {
        $stmt = $this->db->query(
            "SELECT
                COUNT(CASE WHEN status='DONE'    THEN 1 END) AS total_diproses,
                COUNT(CASE WHEN status='PENDING' THEN 1 END) AS antrian_pending,
                MAX(diproses_pada) AS terakhir_aktif
             FROM presensi_antrian
             WHERE DATE(timestamp_masuk) = CURDATE()"
        );
        $stat = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $stmt2 = $this->db->query(
            "SELECT COUNT(*) FROM notifikasi WHERE DATE(dibuat_pada) = CURDATE()"
        );
        $stat['notifikasi_hari_ini'] = (int) $stmt2->fetchColumn();

        $stmt3 = $this->db->query("SELECT COUNT(*) FROM laporan_tersedia");
        $stat['total_laporan'] = (int) $stmt3->fetchColumn();

        if (class_exists('TelegramBot')) {
            $telegram = new TelegramBot();
            $stat['telegram_aktif'] = $telegram->aktif();
            $stat['telegram_status'] = $telegram->statusKonfigurasi();
        } else {
            $stat['telegram_aktif'] = false;
            $stat['telegram_status'] = 'TelegramBot belum dimuat.';
        }

        return $stat;
    }

    public function bacaLog(int $baris = 50): array {
        if (!file_exists($this->logFile)) return [];
        $semua = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return array_slice(array_reverse($semua), 0, $baris);
    }

    public function log(string $pesan): void {
        $baris = "[" . date('Y-m-d H:i:s') . "] $pesan\n";
        file_put_contents($this->logFile, $baris, FILE_APPEND | LOCK_EX);
    }
}
