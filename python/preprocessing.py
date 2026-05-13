"""
Fungsi preprocessing gambar untuk training dan inferensi CNN.
Semua gambar diproses ke ukuran 128x128 RGB ternormalisasi [0, 1].
"""

# pyrefly: ignore [missing-import]
import cv2
# pyrefly: ignore [missing-import]
import numpy as np
from pathlib import Path

UKURAN_INPUT = (128, 128)

# Haar Cascade untuk deteksi wajah
_cascade = None

def _muat_cascade() -> cv2.CascadeClassifier:
    global _cascade
    if _cascade is None:
        path = cv2.data.haarcascades + 'haarcascade_frontalface_default.xml'
        _cascade = cv2.CascadeClassifier(path)
    return _cascade


def deteksi_wajah(gambar_bgr: np.ndarray) -> list[tuple[int, int, int, int]]:
    """Kembalikan daftar koordinat wajah (x, y, w, h) dari gambar BGR."""
    cascade = _muat_cascade()
    abu     = cv2.cvtColor(gambar_bgr, cv2.COLOR_BGR2GRAY)
    abu     = cv2.equalizeHist(abu)
    wajah   = cascade.detectMultiScale(
        abu, scaleFactor=1.1, minNeighbors=5, minSize=(60, 60)
    )
    return list(wajah) if len(wajah) > 0 else []


def crop_dan_resize(gambar_bgr: np.ndarray, x: int, y: int, w: int, h: int) -> np.ndarray:
    """Crop area wajah dan resize ke UKURAN_INPUT."""
    # Tambahkan sedikit margin agar wajah tidak terpotong tepat di tepi
    margin = int(min(w, h) * 0.15)
    x1 = max(0, x - margin)
    y1 = max(0, y - margin)
    x2 = min(gambar_bgr.shape[1], x + w + margin)
    y2 = min(gambar_bgr.shape[0], y + h + margin)

    crop   = gambar_bgr[y1:y2, x1:x2]
    crop   = cv2.cvtColor(crop, cv2.COLOR_BGR2RGB)
    resize = cv2.resize(crop, UKURAN_INPUT, interpolation=cv2.INTER_AREA)
    return resize


def normalisasi(gambar_rgb: np.ndarray) -> np.ndarray:
    """Normalisasi piksel ke rentang [0, 1] sebagai float32."""
    return gambar_rgb.astype(np.float32) / 255.0


def proses_file(path_file: str) -> np.ndarray | None:
    """
    Baca file, deteksi wajah, crop, resize, normalisasi.
    Kembalikan array (128, 128, 3) atau None jika wajah tidak ditemukan.
    """
    gambar = cv2.imread(path_file)
    if gambar is None:
        return None

    wajah = deteksi_wajah(gambar)
    if not wajah:
        # Fallback: proses seluruh gambar tanpa deteksi wajah
        rgb    = cv2.cvtColor(gambar, cv2.COLOR_BGR2RGB)
        resize = cv2.resize(rgb, UKURAN_INPUT, interpolation=cv2.INTER_AREA)
        return normalisasi(resize)

    # Ambil wajah terbesar
    x, y, w, h = max(wajah, key=lambda v: v[2] * v[3])
    crop = crop_dan_resize(gambar, x, y, w, h)
    return normalisasi(crop)


def muat_dataset(dir_dataset: str) -> tuple[np.ndarray, np.ndarray, dict]:
    """
    Baca semua folder NIS di dir_dataset.
    Kembalikan (X, y, label_map) dengan:
        X        : array (N, 128, 128, 3)
        y        : array (N,) integer kelas
        label_map: {indeks: nis}
    """
    root   = Path(dir_dataset)
    folder = sorted([d for d in root.iterdir() if d.is_dir()])

    if not folder:
        raise ValueError(f"Tidak ada folder dataset di {dir_dataset}")

    label_map = {i: d.name for i, d in enumerate(folder)}
    X_list, y_list = [], []

    for idx, folder_nis in enumerate(folder):
        foto_list = list(folder_nis.glob('*.jpg'))
        for foto in foto_list:
            arr = proses_file(str(foto))
            if arr is not None:
                X_list.append(arr)
                y_list.append(idx)

    if not X_list:
        raise ValueError("Tidak ada gambar yang berhasil diproses.")

    return np.array(X_list), np.array(y_list), label_map


def augmentasi(gambar: np.ndarray) -> list[np.ndarray]:
    """
    Augmentasi sederhana: flip horizontal + variasi kecerahan.
    Kembalikan list gambar tambahan (bukan termasuk gambar asli).
    """
    hasil = []

    # Flip horizontal
    flip = gambar[:, ::-1, :]
    hasil.append(flip)

    # Kecerahan +10%
    cerah = np.clip(gambar * 1.10, 0, 1).astype(np.float32)
    hasil.append(cerah)

    # Kecerahan -10%
    redup = np.clip(gambar * 0.90, 0, 1).astype(np.float32)
    hasil.append(redup)

    return hasil
