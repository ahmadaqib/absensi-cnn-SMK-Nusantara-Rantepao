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


def bangun_model(n_kelas: int) -> tf.keras.Model:
    """Arsitektur CNN sesuai PRD §9 Modul 3 + BatchNorm untuk stabilisasi."""
    model = models.Sequential([
        layers.Input(shape=(*UKURAN_INPUT, 3)),

        layers.Conv2D(32, (3, 3), activation='relu', padding='same'),
        layers.BatchNormalization(),
        layers.MaxPooling2D(2, 2),

        layers.Conv2D(64, (3, 3), activation='relu', padding='same'),
        layers.BatchNormalization(),
        layers.MaxPooling2D(2, 2),

        layers.Conv2D(128, (3, 3), activation='relu', padding='same'),
        layers.BatchNormalization(),
        layers.MaxPooling2D(2, 2),

        layers.Flatten(),
        layers.Dense(256, activation='relu'),
        layers.BatchNormalization(),
        layers.Dropout(0.5),
        layers.Dense(n_kelas, activation='softmax'),
    ], name='absensi_cnn')
    return model


class ProgressCallback(callbacks.Callback):
    """Update training_status.json setelah setiap epoch."""
    def __init__(self, total_epoch: int):
        super().__init__()
        self.total_epoch = total_epoch

    def on_epoch_end(self, epoch, logs=None):
        logs     = logs or {}
        pct      = int(((epoch + 1) / self.total_epoch) * 80) + 10  # 10–90%
        akurasi  = round((logs.get('val_accuracy') or logs.get('accuracy', 0)) * 100, 2)
        pesan    = (f"Epoch {epoch+1}/{self.total_epoch} — "
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

    # Bangun dan compile model
    model = bangun_model(n_kelas)
    model.compile(
        optimizer='adam',
        loss='sparse_categorical_crossentropy',
        metrics=['accuracy'],
    )

    n_epoch = 30
    cb_progres = ProgressCallback(n_epoch)
    cb_stop    = callbacks.EarlyStopping(
        monitor='val_accuracy', patience=7, restore_best_weights=True
    )
    cb_lr = callbacks.ReduceLROnPlateau(
        monitor='val_loss', factor=0.5, patience=3, min_lr=1e-6, verbose=0
    )

    # Training
    tulis_status('berjalan', 10, f'Training dimulai ({n_epoch} epoch)...')
    history = model.fit(
        X_train, y_train,
        epochs=n_epoch,
        batch_size=32,
        validation_data=(X_val, y_val),
        callbacks=[cb_progres, cb_stop, cb_lr],
        verbose=0,
    )

    # Evaluasi akhir
    _, akurasi_val = model.evaluate(X_val, y_val, verbose=0)
    akurasi_pct    = round(akurasi_val * 100, 2)
    tulis_status('berjalan', 92, f'Evaluasi selesai. Akurasi validasi: {akurasi_pct:.1f}%', akurasi_pct)

    # Simpan model dan label map
    model.save(str(PATH_MODEL))
    with open(PATH_LABEL, 'w', encoding='utf-8') as f:
        json.dump({str(k): v for k, v in label_map.items()}, f, ensure_ascii=False)

    n_epoch_aktual = len(history.history['loss'])
    tulis_status(
        'selesai', 100,
        f'Training selesai! {n_epoch_aktual} epoch, akurasi: {akurasi_pct:.1f}%',
        akurasi_pct,
    )
    print(f'[OK] Model disimpan → {PATH_MODEL}')
    print(f'[OK] Label map disimpan → {PATH_LABEL}')
    print(f'[OK] Akurasi validasi: {akurasi_pct:.1f}%')


if __name__ == '__main__':
    main()
