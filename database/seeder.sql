USE sistem_absensi;

-- Pengguna (password: "password" — bcrypt hash)
INSERT INTO pengguna (nama, username, password, role) VALUES
('Administrator',    'admin',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Budi Santoso',     'budi',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'guru'),
('Sari Dewi',        'sari',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'guru'),
('Kepala Sekolah',   'kepsek',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kepala_sekolah');
-- Default password semua akun: "password"

-- Kelas
INSERT INTO kelas (nama, tahun) VALUES
('X TKJ 1',   '2025/2026'),
('X TKJ 2',   '2025/2026'),
('XI TKJ 1',  '2025/2026'),
('XI TKJ 2',  '2025/2026'),
('XII TKJ 1', '2025/2026');

-- Siswa demo (kelas XI TKJ 1 = id 3)
INSERT INTO siswa (nama, nis, kelas_id) VALUES
('Andi Saputra',      '2024001', 3),
('Budi Pratama',      '2024002', 3),
('Citra Lestari',     '2024003', 3),
('Dian Purnama',      '2024004', 3),
('Eka Wijaya',        '2024005', 3),
('Fajar Ramadhan',    '2024006', 3),
('Gita Permata',      '2024007', 3),
('Hendra Kusuma',     '2024008', 3),
('Indah Sari',        '2024009', 3),
('Joko Susilo',       '2024010', 3);

-- Jadwal (guru Budi = id 2, guru Sari = id 3)
INSERT INTO jadwal (kelas_id, guru_id, mata_pelajaran, hari, jam_mulai, jam_selesai) VALUES
(3, 2, 'Pemrograman Web',      'Senin',  '07:30', '09:00'),
(3, 2, 'Pemrograman Web',      'Rabu',   '07:30', '09:00'),
(3, 3, 'Basis Data',           'Selasa', '07:30', '09:00'),
(3, 3, 'Basis Data',           'Kamis',  '07:30', '09:00'),
(1, 2, 'Jaringan Komputer',    'Senin',  '09:15', '10:45'),
(2, 3, 'Sistem Operasi',       'Selasa', '09:15', '10:45');
