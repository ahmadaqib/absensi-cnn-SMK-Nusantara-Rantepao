"""
Script training model CNN untuk sistem absensi.
Dijalankan dari command line atau dipanggil oleh PHP via exec().

Output:
  - model_absensi.h5   : model terlatih
  - label_map.json     : mapping {indeks: nis}
  - training_status.json : status dan progres (dibaca PHP saat polling)
"""

import os
import sys
import json
import time
# pyrefly: ignore [missing-import]
import numpy as np
from pathlib import Path

# Cegah TensorFlow menampilkan log verbose
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '2'

import tensorflow as tf
from tensorflow.keras import layers, models, callbacks
from sklearn.model_selection import train_test_split

from preprocessing import muat_dataset, augmentasi, UKURAN_INPUT

DIR_BASE    = Path(__file__).parent
DIR_DATASET = DIR_BASE / 'dataset'
PATH_MODEL  = DIR_BASE / 'model_absensi.h5'
PATH_LABEL  = DIR_BASE / 'label_map.json'
PATH_STATUS = DIR_BASE / 'training_status.json'


def tulis_status(status: str, progres: int, pesan: str,
                 akurasi: float | None = None, error: str | None = None) -> None:
    data = {
        'status'   : status,       # mulai | berjalan | selesai | error
        'progres'  : progres,      # 0–100
        'pesan'    : pesan,
        'akurasi'  : akurasi,
        'error'    : error,
        'waktu'    : time.strftime('%Y-%m-%d %H:%M:%S'),
    }
    PATH_STATUS.write_text(json.dumps(data, ensure_ascii=False), encoding='utf-8')


def bangun_model(n_kelas: int) -> tuple:
    """
    MobileNetV2 transfer learning — backbone ImageNet + classifier head baru.
    Kembalikan (model, base_model). base_model dibutuhkan untuk fine-tuning fase 2.

    Lapisan Rescaling di dalam model mengonversi [0,1] → [-1,1]
    sehingga pipeline normalisasi yang sudah ada tidak perlu diubah.
    """
    # Pertama kali dijalankan: bobot ImageNet (~15 MB) diunduh otomatis oleh TF
    # dan di-cache lokal — tidak perlu internet di run berikutnya.
    base = tf.keras.applications.MobileNetV2(
        weights='imagenet', include_top=False,
        input_shape=(*UKURAN_INPUT, 3)
    )
    base.trainable = False  # Beku semua layer base untuk fase 1

    inputs  = layers.Input(shape=(*UKURAN_INPUT, 3), name='input_gambar')
    # Konversi [0,1] → [-1,1] agar sesuai ekspektasi MobileNetV2
    x       = layers.Rescaling(2.0, offset=-1.0, name='rescale')(inputs)
    x       = base(x, training=False)
    x       = layers.GlobalAveragePooling2D(name='gap')(x)
    x       = layers.Dense(256, activation='relu', name='fc1')(x)
    x       = layers.BatchNormalization(name='bn_fc')(x)
    x       = layers.Dropout(0.4, name='dropout')(x)
    outputs = layers.Dense(n_kelas, activation='softmax', name='output')(x)

    model = tf.keras.Model(inputs, outputs, name='absensi_mobilenetv2')
    return model, base


class ProgressCallback(callbacks.Callback):
    """Update training_status.json setelah setiap epoch."""
    def __init__(self, total_epoch: int, offset: int = 10, batas: int = 90):
        super().__init__()
        self.total_epoch = total_epoch
        self.offset      = offset
        self.batas       = batas

    def on_epoch_end(self, epoch, logs=None):
        logs    = logs or {}
        rentang = self.batas - self.offset
        pct     = self.offset + int(((epoch + 1) / self.total_epoch) * rentang)
        akurasi = round((logs.get('val_accuracy') or logs.get('accuracy', 0)) * 100, 2)
        fase    = 'Fase 1 (head)' if self.batas <= 45 else 'Fase 2 (fine-tune)'
        pesan   = (f"[{fase}] Epoch {epoch+1}/{self.total_epoch} — "
                   f"loss: {logs.get('loss', 0):.4f}, "
                   f"akurasi: {akurasi:.1f}%")
        tulis_status('berjalan', pct, pesan, akurasi)


def main() -> None:
    tulis_status('mulai', 0, 'Memuat dataset...')

    # Muat dataset
    try:
        X, y, label_map = muat_dataset(str(DIR_DATASET))
    except ValueError as e:
        tulis_status('error', 0, str(e), error=str(e))
        sys.exit(1)

    n_kelas  = len(label_map)
    n_sampel = len(X)
    tulis_status('berjalan', 5,
                 f'{n_sampel} gambar dari {n_kelas} siswa dimuat.')

    if n_kelas < 2:
        pesan = 'Minimal 2 siswa dengan dataset untuk training.'
        tulis_status('error', 0, pesan, error=pesan)
        sys.exit(1)

    # Augmentasi
    tulis_status('berjalan', 8, 'Melakukan augmentasi data...')
    X_aug, y_aug = list(X), list(y)
    for gambar, label in zip(X, y):
        for g in augmentasi(gambar):
            X_aug.append(g)
            y_aug.append(label)

    X_aug = np.array(X_aug, dtype=np.float32)
    y_aug = np.array(y_aug, dtype=np.int32)

    # Split train/test
    X_train, X_val, y_train, y_val = train_test_split(
        X_aug, y_aug, test_size=0.2, random_state=42, stratify=y_aug
    )
    tulis_status('berjalan', 10,
                 f'Data: {len(X_train)} train, {len(X_val)} validasi.')

    # Bangun model MobileNetV2
    tulis_status('berjalan', 8, 'Membangun model MobileNetV2 (transfer learning)...')
    model, base_model = bangun_model(n_kelas)

    # ── Fase 1: Latih hanya classifier head — base beku ──────────────────
    model.compile(
        optimizer=tf.keras.optimizers.Adam(1e-3),
        loss='sparse_categorical_crossentropy',
        metrics=['accuracy'],
    )

    N_FASE1  = 10
    cb_p1    = ProgressCallback(N_FASE1, offset=10, batas=45)
    cb_stop1 = callbacks.EarlyStopping(
        monitor='val_accuracy', patience=5, restore_best_weights=True
    )
    cb_lr1   = callbacks.ReduceLROnPlateau(
        monitor='val_loss', factor=0.5, patience=3, min_lr=1e-6, verbose=0
    )

    tulis_status('berjalan', 10, f'Fase 1: Melatih head classifier ({N_FASE1} epoch)...')
    model.fit(
        X_train, y_train, epochs=N_FASE1,
        batch_size=32, validation_data=(X_val, y_val),
        callbacks=[cb_p1, cb_stop1, cb_lr1], verbose=0,
    )

    # ── Fase 2: Fine-tune 30 layer terakhir MobileNetV2 ──────────────────
    base_model.trainable = True
    for layer in base_model.layers[:-30]:
        layer.trainable = False

    # LR sangat kecil agar tidak merusak bobot ImageNet yang sudah baik
    model.compile(
        optimizer=tf.keras.optimizers.Adam(1e-5),
        loss='sparse_categorical_crossentropy',
        metrics=['accuracy'],
    )

    N_FASE2  = 20
    cb_p2    = ProgressCallback(N_FASE2, offset=45, batas=90)
    cb_stop2 = callbacks.EarlyStopping(
        monitor='val_accuracy', patience=7, restore_best_weights=True
    )
    cb_lr2   = callbacks.ReduceLROnPlateau(
        monitor='val_loss', factor=0.5, patience=3, min_lr=1e-7, verbose=0
    )

    tulis_status('berjalan', 45, f'Fase 2: Fine-tuning MobileNetV2 ({N_FASE2} epoch)...')
    history = model.fit(
        X_train, y_train, epochs=N_FASE2,
        batch_size=16, validation_data=(X_val, y_val),
        callbacks=[cb_p2, cb_stop2, cb_lr2], verbose=0,
    )

    # Evaluasi akhir
    _, akurasi_val = model.evaluate(X_val, y_val, verbose=0)
    akurasi_pct    = round(akurasi_val * 100, 2)
    tulis_status('berjalan', 92, f'Evaluasi selesai. Akurasi validasi: {akurasi_pct:.1f}%', akurasi_pct)

    # Simpan model dan label map
    model.save(str(PATH_MODEL))
    with open(PATH_LABEL, 'w', encoding='utf-8') as f:
        json.dump({str(k): v for k, v in label_map.items()}, f, ensure_ascii=False)

    n_epoch_aktual = N_FASE1 + len(history.history['loss'])
    tulis_status(
        'selesai', 100,
        f'Training selesai! {n_epoch_aktual} epoch total, akurasi: {akurasi_pct:.1f}%',
        akurasi_pct,
    )
    print(f'[OK] Model disimpan → {PATH_MODEL}')
    print(f'[OK] Label map disimpan → {PATH_LABEL}')
    print(f'[OK] Akurasi validasi: {akurasi_pct:.1f}%')


if __name__ == '__main__':
    main()
