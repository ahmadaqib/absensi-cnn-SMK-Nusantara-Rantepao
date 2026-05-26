<?php

class Validator {

    private array $kesalahan = [];

    public function wajib(string $field, mixed $nilai, string $label): self {
        if ($nilai === null || trim((string) $nilai) === '') {
            $this->kesalahan[$field] = "$label wajib diisi.";
        }
        return $this;
    }

    public function minPanjang(string $field, string $nilai, int $min, string $label): self {
        if (!isset($this->kesalahan[$field]) && mb_strlen(trim($nilai)) < $min) {
            $this->kesalahan[$field] = "$label minimal $min karakter.";
        }
        return $this;
    }

    public function maksLong(string $field, string $nilai, int $maks, string $label): self {
        if (!isset($this->kesalahan[$field]) && mb_strlen(trim($nilai)) > $maks) {
            $this->kesalahan[$field] = "$label maksimal $maks karakter.";
        }
        return $this;
    }

    public function hanyaAngkaHuruf(string $field, string $nilai, string $label): self {
        if (!isset($this->kesalahan[$field]) && !preg_match('/^[a-zA-Z0-9_]+$/', $nilai)) {
            $this->kesalahan[$field] = "$label hanya boleh berisi huruf, angka, dan underscore.";
        }
        return $this;
    }

    public function valid(): bool {
        return empty($this->kesalahan);
    }

    public function ambilKesalahan(): array {
        return $this->kesalahan;
    }

    public function kesalahan(string $field): ?string {
        return $this->kesalahan[$field] ?? null;
    }
}
