"""
Flask CNN Service — Sistem Absensi SMK Nusantara
Jalankan: python app.py
Port    : 5000
"""

from flask import Flask, request, jsonify
from kenali_wajah import cek_model, kenali, reload_model

app = Flask(__name__)


@app.route('/kenali-wajah', methods=['POST'])
def endpoint_kenali_wajah():
    """
    Request JSON: {"gambar": "data:image/jpeg;base64,..."}
    Response JSON: sesuai PRD §10
    """
    data = request.get_json(silent=True)

    if not data or 'gambar' not in data:
        return jsonify({
            'status': 'error',
            'pesan' : 'Field "gambar" wajib ada dalam request body.',
        }), 400

    hasil = kenali(data['gambar'])
    kode  = 200 if hasil['status'] in ('berhasil', 'gagal') else 422
    return jsonify(hasil), kode


@app.route('/reload-model', methods=['POST'])
def endpoint_reload_model():
    """Dipanggil PHP setelah training selesai agar model terbaru dimuat."""
    reload_model()
    return jsonify({'status': 'ok', 'pesan': 'Model akan dimuat ulang pada request berikutnya.'})


@app.route('/status', methods=['GET'])
def endpoint_status():
    """Health check sederhana."""
    status_model = cek_model()
    return jsonify({
        'status'   : 'aktif',
        'model_ada': status_model['model_ada'],
        'model_siap': status_model['model_siap'],
        'pesan'    : status_model['pesan'],
    })


if __name__ == '__main__':
    # Debug=False untuk lingkungan sekolah — tidak perlu hot-reload
    app.run(host='127.0.0.1', port=5001, debug=False)
