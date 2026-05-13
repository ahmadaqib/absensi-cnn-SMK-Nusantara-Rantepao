"""
Fungsi preprocessing gambar untuk training dan inferensi CNN.
Semua gambar diproses ke ukuran 224x224 RGB ternormalisasi [0, 1].
"""

# pyrefly: ignore [missing-import]
import cv2
# pyrefly: ignore [missing-import]
import numpy as np
from pathlib import Path

UKURAN_INPUT = (224, 224)

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
    Kembalikan array (224, 224, 3) atau None jika wajah tidak ditemukan.
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
        X        : array (N, 224, 224, 3)
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


def _rotasi(gambar: np.ndarray, sudut: float) -> np.ndarray:
    """Rotasi gambar sebesar `sudut` derajat, lalu crop kembali ke ukuran asli."""
    h, w = gambar.shape[:2]
    M = cv2.getRotationMatrix2D((w / 2, h / 2), sudut, 1.0)
    hasil = cv2.warpAffine(gambar, M, (w, h), borderMode=cv2.BORDER_REFLECT_101)
    return hasil.astype(np.float32)


def _zoom(gambar: np.ndarray, faktor: float) -> np.ndarray:
    """Zoom in/out gambar. faktor > 1 = zoom in, < 1 = zoom out."""
    h, w = gambar.shape[:2]
    new_h, new_w = int(h * faktor), int(w * faktor)
    resized = cv2.resize(gambar, (new_w, new_h), interpolation=cv2.INTER_LINEAR)
    if faktor >= 1.0:
        # Crop tengah
        y0 = (new_h - h) // 2
        x0 = (new_w - w) // 2
        return resized[y0:y0+h, x0:x0+w].astype(np.float32)
    else:
        # Pad dengan reflect
        pad_y = (h - new_h) // 2
        pad_x = (w - new_w) // 2
        padded = cv2.copyMakeBorder(resized, pad_y, h - new_h - pad_y,
                                    pad_x, w - new_w - pad_x,
                                    cv2.BORDER_REFLECT_101)
        return padded.astype(np.float32)


def _noise_gaussian(gambar: np.ndarray, sigma: float = 0.02) -> np.ndarray:
    """Tambah noise Gaussian ringan."""
    noise = np.random.normal(0, sigma, gambar.shape).astype(np.float32)
    return np.clip(gambar + noise, 0, 1).astype(np.float32)


def _blur_ringan(gambar: np.ndarray, ksize: int = 3) -> np.ndarray:
    """Gaussian blur ringan untuk simulasi kamera kurang fokus."""
    return cv2.GaussianBlur(gambar, (ksize, ksize), 0).astype(np.float32)


def augmentasi(gambar: np.ndarray) -> list[np.ndarray]:
    """
    Augmentasi diperkaya: flip, rotasi, zoom, noise, blur, kecerahan.
    Menghasilkan ~10 variasi per gambar untuk meningkatkan confidence CNN.
    Kembalikan list gambar tambahan (bukan termasuk gambar asli).
    """
    hasil = []

    # 1. Flip horizontal
    flip = gambar[:, ::-1, :].copy()
    hasil.append(flip)

    # 2. Rotasi +10°
    hasil.append(_rotasi(gambar, 10))

    # 3. Rotasi -10°
    hasil.append(_rotasi(gambar, -10))

    # 4. Rotasi +15° (lebih ekstrem)
    hasil.append(_rotasi(gambar, 15))

    # 5. Zoom in 1.1x
    hasil.append(_zoom(gambar, 1.10))

    # 6. Zoom out 0.9x
    hasil.append(_zoom(gambar, 0.90))

    # 7. Kecerahan +20%
    cerah = np.clip(gambar * 1.20, 0, 1).astype(np.float32)
    hasil.append(cerah)

    # 8. Kecerahan -20%
    redup = np.clip(gambar * 0.80, 0, 1).astype(np.float32)
    hasil.append(redup)

    # 9. Gaussian noise ringan
    hasil.append(_noise_gaussian(gambar, sigma=0.02))

    # 10. Blur ringan + flip (simulasi kamera kurang fokus)
    hasil.append(_blur_ringan(flip, ksize=3))

    return hasil
