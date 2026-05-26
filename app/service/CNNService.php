<?php

/**
 * Wrapper HTTP ke Python Flask CNN service.
 * Semua komunikasi via cURL POST JSON.
 */
class CNNService {

    private string $baseUrl;
    private int    $timeout; // detik
    private ?string $lastError = null;

    public function __construct() {
        $this->baseUrl = CNN_SERVICE_URL;
        $this->timeout = 25;
    }

    /**
     * Kirim gambar base64 ke /kenali-wajah.
     * Kembalikan array hasil atau null jika service tidak bisa dihubungi.
     */
    public function kenaliWajah(string $gambarBase64): ?array {
        return $this->post('/kenali-wajah', ['gambar' => $gambarBase64]);
    }

    /**
     * Minta Flask reload model setelah training selesai.
     */
    public function reloadModel(): bool {
        $hasil = $this->post('/reload-model', []);
        return ($hasil['status'] ?? '') === 'ok';
    }

    /**
     * Cek apakah Flask service aktif.
     */
    public function cekStatus(): array {
        $hasil = $this->get('/status');
        return $hasil ?? ['status' => 'mati', 'model_ada' => false, 'pesan' => 'CNN service tidak bisa dihubungi.'];
    }

    public function getLastError(): ?string {
        return $this->lastError;
    }

    private function post(string $path, array $data): ?array {
        $this->lastError = null;
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $respons = curl_exec($ch);
        $errno   = curl_errno($ch);
        $error   = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($errno || $respons === false) {
            $this->lastError = $error ?: 'cURL gagal tanpa pesan error.';
            return null;
        }

        $json = json_decode($respons, true);
        if (!is_array($json)) {
            $ringkas = trim(strip_tags(substr($respons, 0, 200)));
            return [
                'status' => 'error',
                'pesan'  => 'CNN service merespons tetapi bukan JSON valid'
                    . ($httpCode ? " (HTTP $httpCode)" : '')
                    . ($ringkas ? ": $ringkas" : '.'),
            ];
        }

        return $json;
    }

    private function get(string $path): ?array {
        $this->lastError = null;
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $respons = curl_exec($ch);
        $errno   = curl_errno($ch);
        $error   = curl_error($ch);
        curl_close($ch);

        if ($errno || $respons === false) {
            $this->lastError = $error ?: 'cURL gagal tanpa pesan error.';
            return null;
        }

        $json = json_decode($respons, true);
        return is_array($json) ? $json : null;
    }
}
