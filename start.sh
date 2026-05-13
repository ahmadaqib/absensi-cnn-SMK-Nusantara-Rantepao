#!/bin/bash
# start.sh — Jalankan sistem absensi CNN (PHP + Python Flask)
# Penggunaan: ./start.sh
# Tekan Ctrl+C untuk menghentikan keduanya sekaligus.

clear
echo "============================================"
echo "  Sistem Absensi Wajah CNN"
echo "  SMK Nusantara Rantepao"
echo "============================================"
echo ""

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

# Cek PHP tersedia
if ! command -v php &>/dev/null; then
    echo "[ERROR] PHP tidak ditemukan."
    echo "        Install XAMPP dari https://www.apachefriends.org"
    echo "        Atau install PHP via Homebrew: brew install php"
    exit 1
fi

# Cek Python tersedia: virtualenv lokal, python, lalu python3
PYTHON_CMD=""
if [ -x "$SCRIPT_DIR/python/.venv/bin/python" ]; then
    PYTHON_CMD="$SCRIPT_DIR/python/.venv/bin/python"
elif command -v python &>/dev/null; then
    PYTHON_CMD="python"
elif command -v python3 &>/dev/null; then
    PYTHON_CMD="python3"
else
    echo "[ERROR] Python tidak ditemukan."
    echo "        Install Python dari https://www.python.org/downloads"
    exit 1
fi

# Cek MySQL jalan (opsional)
if ! mysqladmin -u root --connect-timeout=2 ping &>/dev/null 2>&1; then
    echo "[PERINGATAN] MySQL belum berjalan."
    echo "             Buka XAMPP → manager-osx → Start MySQL Database"
    echo ""
    read -p "Tekan Enter untuk tetap melanjutkan, atau Ctrl+C untuk batal... "
fi

# Hentikan kedua proses saat script dihentikan (Ctrl+C)
FLASK_PID=""
cleanup() {
    echo ""
    echo "Menghentikan semua proses..."
    [ -n "$FLASK_PID" ] && kill "$FLASK_PID" 2>/dev/null
    echo "Selesai."
    exit 0
}
trap cleanup EXIT INT TERM

# 1. Jalankan Flask di background
echo "[1/2] Memulai Python CNN Service di port 5000..."
cd "$SCRIPT_DIR/python"
$PYTHON_CMD app.py &
FLASK_PID=$!
cd "$SCRIPT_DIR"

# Tunggu Flask siap dan pastikan endpoint status benar-benar menjawab.
for i in {1..20}; do
    if curl -fsS http://127.0.0.1:5000/status >/dev/null 2>&1; then
        break
    fi
    if ! kill -0 "$FLASK_PID" 2>/dev/null; then
        echo "[ERROR] Flask gagal dijalankan. Cek output di atas."
        exit 1
    fi
    sleep 1
done

if ! curl -fsS http://127.0.0.1:5000/status >/dev/null 2>&1; then
    echo "[ERROR] CNN Service belum menjawab di http://127.0.0.1:5000/status"
    exit 1
fi
echo "        Flask berjalan (PID: $FLASK_PID)"

# 2. Jalankan PHP built-in server (foreground — blocking)
echo "[2/2] Memulai Web Server PHP di port 8000..."
echo ""
echo "============================================"
echo "  Sistem siap!"
echo "  Buka browser: http://localhost:8000"
echo ""
echo "  Login: admin / password"
echo ""
echo "  Tekan Ctrl+C untuk menghentikan semua."
echo "============================================"
echo ""

php -S localhost:8000 "$SCRIPT_DIR/router.php"
