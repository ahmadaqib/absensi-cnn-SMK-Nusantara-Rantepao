"""
Modul pengenalan wajah menggunakan model CNN yang sudah dilatih.
"""

import json
import base64
import numpy as np
import cv2
from pathlib import Path

import tensorflow as tf

from preprocessing import deteksi_wajah, crop_dan_resize, normalisasi, UKURAN_INPUT

DIR_BASE    = Path(__file__).parent
PATH_MODEL  = DIR_BASE / 'model_absensi.h5'
PATH_LABEL  = DIR_BASE / 'label_map.json'

# Model dan label dimuat sekali saat Flask start (bukan tiap request)
_model     = None
_label_map = None   # {indeks_str: nis}


def _muat_model():
    global _model, _label_map
    if _model is None:
        if not PATH_MODEL.exists():
            raise FileNotFoundError('model_absensi.h5 tidak ditemukan. Lakukan training terlebih dahulu.')
        if not PATH_LABEL.exists():
            raise FileNotFoundError('label_map.json tidak ditemukan.')

        _model     = tf.keras.models.load_model(str(PATH_MODEL))
        with open(PATH_LABEL, 'r', encoding='utf-8') as f:
            _label_map = json.load(f)


def base64_ke_array(data_base64: str) -> np.ndarray | None:
    """Decode string base64 (dengan atau tanpa header data URI) ke array BGR."""
    try:
        # Hapus header data URI jika ada
        if ',' in data_base64:
            data_base64 = data_base64.split(',', 1)[1]

        byte_data = base64.b64decode(data_base64)
        arr       = np.frombuffer(byte_data, dtype=np.uint8)
        gambar    = cv2.imdecode(arr, cv2.IMREAD_COLOR)
        return gambar
    except Exception:
        return None


def kenali(data_base64: str) -> dict:
    """
    Terima gambar base64, kembalikan dict hasil pengenalan:
      status: 'berhasil' | 'gagal' | 'error'
      nis, nama (jika berhasil)
      confidence (float)
      pesan (string)
    """
    try:
        _muat_model()
    except Exception as e:
        return {'status': 'error', 'pesan': f'Model CNN gagal dimuat: {e}'}

    gambar = base64_ke_array(data_base64)
    if gambar is None:
        return {'status': 'error', 'pesan': 'Gambar tidak dapat dibaca.'}

    wajah = deteksi_wajah(gambar)
    if not wajah:
        return {'status': 'error', 'pesan': 'Tidak ada wajah terdeteksi dalam gambar.'}

    # Ambil wajah terbesar (paling dekat kamera)
    x, y, w, h = max(wajah, key=lambda v: v[2] * v[3])
    crop  = crop_dan_resize(gambar, x, y, w, h)
    norm  = normalisasi(crop)

    # Inferensi
    try:
        input_arr = np.expand_dims(norm, axis=0)   # (1, 224, 224, 3)
        prediksi  = _model.predict(input_arr, verbose=0)[0]
    except Exception as e:
        return {'status': 'error', 'pesan': f'Inferensi CNN gagal: {e}'}

    indeks_kelas = int(np.argmax(prediksi))
    confidence   = float(prediksi[indeks_kelas])
    nis          = _label_map.get(str(indeks_kelas), '')

    if confidence >= 0.85:
        return {
            'status'    : 'berhasil',
            'nis'       : nis,
            'confidence': round(confidence, 4),
            'pesan'     : 'Wajah dikenali.',
        }
    elif confidence >= 0.70:
        return {
            'status'    : 'gagal',
            'confidence': round(confidence, 4),
            'pesan'     : 'Wajah tidak dikenali. Coba lagi dengan pencahayaan lebih baik.',
        }
    else:
        return {
            'status'    : 'gagal',
            'confidence': round(confidence, 4),
            'pesan'     : 'Wajah tidak dikenali. Pastikan wajah menghadap kamera.',
        }


def cek_model() -> dict:
    """Validasi file model dan muat model sekali agar status benar-benar siap."""
    if not PATH_MODEL.exists():
        return {
            'model_ada': False,
            'model_siap': False,
            'pesan': 'Model belum ada. Lakukan training terlebih dahulu.',
        }

    if not PATH_LABEL.exists():
        return {
            'model_ada': True,
            'model_siap': False,
            'pesan': 'label_map.json tidak ditemukan. Lakukan training ulang.',
        }

    try:
        _muat_model()
    except Exception as e:
        return {
            'model_ada': True,
            'model_siap': False,
            'pesan': f'Model CNN gagal dimuat: {e}',
        }

    return {
        'model_ada': True,
        'model_siap': True,
        'pesan': 'CNN service berjalan dan model siap.',
    }


def reload_model() -> None:
    """Paksa muat ulang model (dipanggil setelah training selesai)."""
    global _model, _label_map
    _model     = None
    _label_map = None
