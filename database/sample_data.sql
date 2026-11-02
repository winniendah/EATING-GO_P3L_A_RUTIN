-- Import this file after schema.sql has created the eating_go database.
USE eating_go;

INSERT IGNORE INTO cafes
    (id, name, description, address, city, phone, opening_time, closing_time, image_url, status)
VALUES
    (1, 'Kopi Senja', 'Kafe nyaman untuk bekerja dan menikmati kopi lokal.',
     'Jl. Braga No. 12', 'Bandung', '0224200101', '08:00:00', '22:00:00', NULL, 'aktif'),
    (2, 'Ruang Rasa', 'Tempat berkumpul dengan pilihan makanan dan minuman beragam.',
     'Jl. Dipatiukur No. 45', 'Bandung', '0224200102', '09:00:00', '23:00:00', NULL, 'aktif'),
    (3, 'Pagi Cerita', 'Kafe dengan suasana tenang untuk memulai hari.',
     'Jl. Malioboro No. 8', 'Yogyakarta', '02744200103', '07:00:00', '21:00:00', NULL, 'aktif');

-- Semua akun contoh menggunakan kata sandi: eatinggo123
INSERT IGNORE INTO users
    (id, cafe_id, name, email, password_hash, phone, role)
VALUES
    (1, NULL, 'Pelanggan Contoh', 'pelanggan@example.test',
     '$2y$12$RiFiFaOw43q0HU7E6yieTeIMa2.ihqnLBTchuh4rUdhq9OSHUs8Gq', '081234567801', 'pelanggan'),
    (2, NULL, 'Admin Contoh', 'admin@example.test',
     '$2y$12$RiFiFaOw43q0HU7E6yieTeIMa2.ihqnLBTchuh4rUdhq9OSHUs8Gq', '081234567802', 'admin'),
    (3, 1, 'Karyawan Kopi Senja', 'karyawan@example.test',
     '$2y$12$RiFiFaOw43q0HU7E6yieTeIMa2.ihqnLBTchuh4rUdhq9OSHUs8Gq', '081234567803', 'karyawan');

INSERT IGNORE INTO cafe_tables
    (id, cafe_id, table_code, capacity)
VALUES
    (1, 1, 'A1', 2),
    (2, 1, 'A2', 4),
    (3, 1, 'A3', 4),
    (4, 2, 'B1', 2),
    (5, 2, 'B2', 6),
    (6, 3, 'C1', 4);

INSERT IGNORE INTO reservations
    (id, user_id, cafe_table_id, reservation_date, start_time, end_time, guest_count, status, notes)
VALUES
    (1, 1, 2, DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY), '18:00:00', '20:00:00', 3, 'dikonfirmasi', 'Meja dekat jendela jika tersedia.'),
    (2, 1, 4, DATE_ADD(CURRENT_DATE, INTERVAL 2 DAY), '10:00:00', '12:00:00', 2, 'menunggu', NULL);