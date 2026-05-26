<?php

/**
 * Client kecil untuk Telegram Bot API.
 * Token dan chat id dibaca dari config/app.php.
 */
class TelegramBot {

    private string $token;
    private string $chatId;

    public function __construct(?string $token = null, ?string $chatId = null) {
        $this->token  = trim($token ?? TELEGRAM_BOT_TOKEN);
        $this->chatId = trim($chatId ?? TELEGRAM_CHAT_ID);
    }

    public function aktif(): bool {
        return $this->token !== '' && $this->chatId !== '' && function_exists('curl_init');
    }

    public function statusKonfigurasi(): string {
        if ($this->token === '' || $this->chatId === '') {
            return 'Token/chat id Telegram belum dikonfigurasi.';
        }
        if (!function_exists('curl_init')) {
            return 'Ekstensi cURL PHP belum aktif.';
        }
        return 'Telegram siap.';
    }

    public function kirimPesan(string $pesan): array {
        if (!$this->aktif()) {
            return ['ok' => false, 'error' => $this->statusKonfigurasi()];
        }

        $url = 'https://api.telegram.org/bot' . $this->token . '/sendMessage';
        $payload = [
            'chat_id' => $this->chatId,
            'text' => $pesan,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
        ]);

        $respons = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($errno || $respons === false) {
            return ['ok' => false, 'error' => $error ?: 'cURL gagal tanpa pesan error.'];
        }

        $json = json_decode($respons, true);
        if ($httpCode < 200 || $httpCode >= 300 || !is_array($json) || empty($json['ok'])) {
            $deskripsi = is_array($json) ? ($json['description'] ?? $respons) : $respons;
            return ['ok' => false, 'error' => "Telegram HTTP $httpCode: " . substr((string) $deskripsi, 0, 180)];
        }

        return ['ok' => true, 'error' => null];
    }

    public static function escape(string $teks): string {
        return htmlspecialchars($teks, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
