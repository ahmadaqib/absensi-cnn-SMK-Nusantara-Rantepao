<?php

class NotifikasiController {

    private Notifikasi $notifikasiModel;

    public function __construct() {
        Auth::cekLogin();
        $this->notifikasiModel = new Notifikasi();
    }

    public function cek(): void {
        $penggunaId = Auth::idSaatIni();
        if (!$penggunaId) {
            Response::json(['jumlah' => 0, 'data' => []], 401);
            return;
        }

        Response::json([
            'jumlah' => $this->notifikasiModel->hitungBelumDibaca($penggunaId),
            'data'   => $this->notifikasiModel->ambilTerbaru($penggunaId),
        ]);
    }

    public function baca(): void {
        $penggunaId = Auth::idSaatIni();
        if (!$penggunaId) {
            Response::json(['status' => 'error', 'pesan' => 'Sesi tidak valid.'], 401);
            return;
        }

        $id = isset($_POST['id']) && $_POST['id'] !== '' ? (int) $_POST['id'] : null;
        $this->notifikasiModel->tandaiDibaca($penggunaId, $id);

        Response::json([
            'status' => 'ok',
            'jumlah' => $this->notifikasiModel->hitungBelumDibaca($penggunaId),
        ]);
    }
}
