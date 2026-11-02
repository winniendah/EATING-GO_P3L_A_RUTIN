CREATE DATABASE IF NOT EXISTS eating_go
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE eating_go;

CREATE TABLE IF NOT EXISTS cafes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    address VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NULL,
    opening_time TIME NOT NULL,
    closing_time TIME NOT NULL,
    image_url VARCHAR(500) NULL,
    status ENUM('aktif', 'nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_cafes_city_status (city, status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cafe_id INT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(191) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    role ENUM('pelanggan', 'admin', 'karyawan') NOT NULL DEFAULT 'pelanggan',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_cafe_role (cafe_id, role),
    CONSTRAINT fk_users_cafe
        FOREIGN KEY (cafe_id) REFERENCES cafes (id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cafe_tables (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cafe_id INT UNSIGNED NOT NULL,
    table_code VARCHAR(30) NOT NULL,
    capacity TINYINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cafe_tables_code (cafe_id, table_code),
    KEY idx_cafe_tables_cafe_capacity (cafe_id, capacity),
    CONSTRAINT chk_cafe_tables_capacity CHECK (capacity > 0),
    CONSTRAINT fk_cafe_tables_cafe
        FOREIGN KEY (cafe_id) REFERENCES cafes (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reservations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED NOT NULL,
    cafe_table_id INT UNSIGNED NOT NULL,
    reservation_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    guest_count TINYINT UNSIGNED NOT NULL,
    status ENUM('menunggu', 'dikonfirmasi', 'selesai', 'dibatalkan') NOT NULL DEFAULT 'menunggu',
    notes VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_reservations_user_date (user_id, reservation_date),
    KEY idx_reservations_table_schedule (cafe_table_id, reservation_date, start_time, end_time),
    CONSTRAINT chk_reservations_guest_count CHECK (guest_count > 0),
    CONSTRAINT chk_reservations_time_order CHECK (end_time > start_time),
    CONSTRAINT fk_reservations_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT fk_reservations_cafe_table
        FOREIGN KEY (cafe_table_id) REFERENCES cafe_tables (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Check table capacity and overlapping reservation times in the application.