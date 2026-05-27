<?php

class JadwalController {

    private Jadwal  $jadwalModel;
    private Kelas   $kelasModel;
    private Pengguna $penggunaModel;

    public function __construct() {
        Auth::cekRole(['admin']);
        $this->jadwalModel   = new Jadwal();
        $this->kelasModel    = new Kelas();
        $this->penggunaModel = new Pengguna();
    }

    public function index(): void {
        $daftarJadwal = $this->jadwalModel->ambilSemua();
        $daftarKelas  = $this->kelasModel->ambilSemua();
        $daftarGuru   = $this->penggunaModel->ambilSemua('guru');
        $judulHalaman = 'Jadwal Pelajaran';

        require_once BASE_PATH . '/views/layouts/header.php';
        require_once BASE_PATH . '/views/layouts/sidebar.php';
        require_once BASE_PATH . '/views/jadwal/index.php';
        require_once BASE_PATH . '/views/layouts/footer.php';
    }

    public function simpan(): void {
        $id           = (int) ($_POST['id'] ?? 0);
        $kelasId      = (int) ($_POST['kelas_id'] ?? 0);
        $guruId       = (int) ($_POST['guru_id'] ?? 0);
        $mapel        = trim($_POST['mata_pelajaran'] ?? '');
        $hari         = trim($_POST['hari'] ?? '');
        $jamMulai     = trim($_POST['jam_mulai'] ?? '');
        $jamSelesai   = trim($_POST['jam_selesai'] ?? '');

        $validator = new Validator();
        $validator->wajib('kelas_id',       $kelasId ?: '',  'Kelas')
                  ->wajib('guru_id',        $guruId ?: '',   'Guru')
                  ->wajib('mata_pelajaran', $mapel,          'Mata pelajaran')
                  ->wajib('hari',           $hari,           'Hari')
                  ->wajib('jam_mulai',      $jamMulai,       'Jam mulai')
                  ->wajib('jam_selesai',    $jamSelesai,     'Jam selesai');

        if (!$validator->valid()) {
            Response::redirectDenganPesan('jadwal', 'gagal',
                implode(' ', $validator->ambilKesalahan()));
            return;
        }

        $data = [
            'kelas_id'      => $kelasId,
            'guru_id'       => $guruId,
            'mata_pelajaran'=> $mapel,
            'hari'          => $hari,
            'jam_mulai'     => $jamMulai,
            'jam_selesai'   => $jamSelesai,
        ];

        if ($id > 0) {
            $this->jadwalModel->update($id, $data);
            Response::redirectDenganPesan('jadwal', 'sukses', "Jadwal $mapel berhasil diperbarui.");
        } else {
            $this->jadwalModel->simpan($data);
            Response::redirectDenganPesan('jadwal', 'sukses', "Jadwal $mapel berhasil ditambahkan.");
        }
    }

    public function hapus(): void {
        $id = (int) ($_POST['id'] ?? 0);
        $jadwal = $this->jadwalModel->cariById($id);
        if (!$jadwal) {
            Response::redirectDenganPesan('jadwal', 'gagal', 'Data jadwal tidak ditemukan.');
            return;
        }

        $db = koneksiDB();
        
        // Cek apakah jadwal sudah digunakan untuk absensi final siswa atau guru
        $stmtAbs = $db->prepare("
            SELECT (
                SELECT COUNT(*) FROM absensi WHERE jadwal_id = ?
            ) + (
                SELECT COUNT(*) FROM absensi_guru WHERE jadwal_id = ?
            ) AS total
        ");
        $stmtAbs->execute([$id, $id]);
        if ((int) $stmtAbs->fetchColumn() > 0) {
            Response::redirectDenganPesan('jadwal', 'gagal', 
                'Jadwal tidak bisa dihapus karena sudah memiliki rekaman absensi siswa atau guru.');
            return;
        }

        try {
            $db->beginTransaction();
            
            // Hapus data antrian presensi rpa terkait
            $stmtAntrian = $db->prepare("DELETE FROM presensi_antrian WHERE jadwal_id = ?");
            $stmtAntrian->execute([$id]);
            
            // Hapus data notifikasi terkirim terkait
            $stmtNotif = $db->prepare("DELETE FROM notifikasi_terkirim WHERE jadwal_id = ?");
            $stmtNotif->execute([$id]);

            // Hapus jadwal
            $stmtJadwal = $db->prepare("DELETE FROM jadwal WHERE id = ?");
            $stmtJadwal->execute([$id]);

            $db->commit();
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }

        Response::redirectDenganPesan('jadwal', 'sukses', 'Jadwal berhasil dihapus.');
    }
}
