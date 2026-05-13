@echo off
title Sistem Absensi CNN - SMK Nusantara
color 0A
cls

echo ============================================
echo   Sistem Absensi Wajah CNN
echo   SMK Nusantara Rantepao
echo ============================================
echo.

:: Cek PHP tersedia
where php >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] PHP tidak ditemukan.
    echo         Pastikan XAMPP terinstall dan tambahkan ke PATH:
    echo         C:\xampp\php\
    echo.
    pause
    exit /b 1
)

:: Cek Python tersedia: virtualenv lokal, lalu python global
set "PYTHON_DIR=%~dp0python"
set "PYTHON_CMD=python"
if exist "%PYTHON_DIR%\.venv\Scripts\python.exe" (
    set "PYTHON_CMD=%PYTHON_DIR%\.venv\Scripts\python.exe"
) else (
    where python >nul 2>&1
    if %errorlevel% neq 0 (
        echo [ERROR] Python tidak ditemukan.
        echo         Install Python dari https://python.org
        echo         Centang "Add Python to PATH" saat instalasi.
        echo.
        pause
        exit /b 1
    )
)

:: Cek MySQL XAMPP jalan (opsional, hanya peringatan)
mysqladmin -u root --connect-timeout=2 ping >nul 2>&1
if %errorlevel% neq 0 (
    echo [PERINGATAN] MySQL sepertinya belum berjalan.
    echo              Buka XAMPP Control Panel dan Start MySQL.
    echo.
    echo Lanjutkan? Tekan Enter untuk melanjutkan, atau Ctrl+C untuk batal.
    pause >nul
)

echo [1/2] Memulai Python CNN Service di port 5000...
start "CNN Service (port 5000)" cmd /k "cd /d ^"%PYTHON_DIR%^" && ^"%PYTHON_CMD%^" app.py"

:: Tunggu Flask siap
timeout /t 2 /nobreak >nul
powershell -NoProfile -Command "try { Invoke-WebRequest -UseBasicParsing http://127.0.0.1:5000/status -TimeoutSec 8 | Out-Null; exit 0 } catch { exit 1 }" >nul 2>&1
if %errorlevel% neq 0 (
    echo [PERINGATAN] CNN Service belum menjawab di http://127.0.0.1:5000/status
    echo              Cek jendela "CNN Service (port 5000)" untuk error Python.
    echo.
)

echo [2/2] Memulai Web Server PHP di port 8000...
echo.
echo ============================================
echo   Sistem siap!
echo   Buka browser: http://localhost:8000
echo.
echo   Login: admin / password
echo.
echo   Tutup jendela ini untuk menghentikan
echo   web server (Flask tetap jalan di jendela
echo   "CNN Service").
echo ============================================
echo.

php -S localhost:8000 "%~dp0router.php"
