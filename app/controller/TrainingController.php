<?php

class TrainingController {

    private string $pathStatus;
    private string $pathPython;
    private string $dirDataset;

    public function __construct() {
        Auth::cekRole(['admin']);
        $this->pathStatus = BASE_PATH . '/python/training_status.json';
        $this->pathPython  = BASE_PATH . '/python/latih_model.py';
        $this->dirDataset  = BASE_PATH . '/python/dataset/';
    }

    public function index(): void {
        $statusTraining = $this->bacaStatus();
        $infoDataset    = $this->infoDataset();
        $judulHalaman   = 'Training Model CNN';

        require_once BASE_PATH . '/views/layouts/header.php';
        require_once BASE_PATH . '/views/layouts/sidebar.php';
        require_once BASE_PATH . '/views/training/index.php';
        require_once BASE_PATH . '/views/layouts/footer.php';
    }

    public function mulai(): void {
        $info = $this->infoDataset();

        if ($info['jumlah_siswa'] < 2) {
            Response::redirectDenganPesan('training', 'gagal',
                'Minimal 2 siswa dengan dataset (masing-masing ≥5 foto) untuk memulai training.');
            return;
        }

        // Cek apakah training sedang berjalan
        $status = $this->bacaStatus();
        if (($status['status'] ?? '') === 'berjalan' || ($status['status'] ?? '') === 'mulai') {
            Response::redirectDenganPesan('training', 'gagal', 'Training sedang berjalan. Tunggu hingga selesai.');
            return;
        }

        // Reset status sebelum mulai
        file_put_contents($this->pathStatus, json_encode([
            'status'  => 'mulai',
            'progres' => 0,
            'pesan'   => 'Menginisialisasi training...',
            'akurasi' => null,
            'error'   => null,
            'waktu'   => date('Y-m-d H:i:s'),
        ]));

        // Jalankan Python di background
        $cmd = $this->perintahPython() . ' ' . escapeshellarg($this->pathPython);
        $this->jalankanBackground($cmd);

        Response::redirectDenganPesan('training', 'sukses', 'Training dimulai. Halaman akan memperbarui otomatis.');
    }

    // Endpoint polling — dipanggil JS setiap 3 detik
    public function status(): void {
        $status = $this->bacaStatus();
        if (($status['status'] ?? '') === 'selesai' && class_exists('CNNService')) {
            (new CNNService())->reloadModel();
        }
        Response::json($status);
    }

    private function bacaStatus(): array {
        if (!file_exists($this->pathStatus)) {
            return ['status' => 'idle', 'progres' => 0, 'pesan' => 'Belum ada training.', 'akurasi' => null];
        }
        $data = json_decode(file_get_contents($this->pathStatus), true);
        return is_array($data) ? $data : ['status' => 'idle', 'progres' => 0, 'pesan' => ''];
    }

    private function infoDataset(): array {
        if (!is_dir($this->dirDataset)) {
            return ['jumlah_siswa' => 0, 'detail' => []];
        }

        $detail = [];
        foreach (scandir($this->dirDataset) as $nis) {
            if ($nis === '.' || $nis === '..') continue;
            $dir = $this->dirDataset . $nis . '/';
            if (!is_dir($dir)) continue;
            $jumlahFoto = count(glob($dir . '*.jpg'));
            $detail[]   = ['nis' => $nis, 'jumlah_foto' => $jumlahFoto, 'cukup' => $jumlahFoto >= 5];
        }

        return [
            'jumlah_siswa' => count(array_filter($detail, fn($d) => $d['cukup'])),
            'detail'       => $detail,
        ];
    }

    private function perintahPython(): string {
        if (defined('PYTHON_BIN') && PYTHON_BIN !== '') {
            return escapeshellarg(PYTHON_BIN);
        }

        $venvPython = PHP_OS_FAMILY === 'Windows'
            ? BASE_PATH . '/python/.venv/Scripts/python.exe'
            : BASE_PATH . '/python/.venv/bin/python';

        if (is_file($venvPython)) {
            return escapeshellarg($venvPython);
        }

        // Fallback untuk instalasi tanpa virtualenv.
        if (PHP_OS_FAMILY === 'Windows') {
            return 'python';
        }

        return $this->commandAda('python') ? 'python' : 'python3';
    }

    private function commandAda(string $command): bool {
        $hasil = shell_exec('command -v ' . escapeshellarg($command) . ' 2>/dev/null');
        return is_string($hasil) && trim($hasil) !== '';
    }

    private function jalankanBackground(string $cmd): void {
        if (PHP_OS_FAMILY === 'Windows') {
            pclose(popen("start /B $cmd", 'r'));
        } else {
            // Redirect output agar tidak hang; jalankan sebagai background process
            exec("$cmd > /dev/null 2>&1 &");
        }
    }
}
