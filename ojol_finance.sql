-- Database: ojol_finance
-- Buat database baru
CREATE DATABASE IF NOT EXISTS ojol_finance;
USE ojol_finance;

-- Tabel untuk menyimpan entri pendapatan
CREATE TABLE IF NOT EXISTS entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    note VARCHAR(255) DEFAULT '-',
    platform ENUM('GrabBike', 'GoRide', 'ShopeeFood', 'GrabFood', 'GoFood', 'Maxim', 'Lainnya') DEFAULT 'Lainnya',
    start_time TIME NULL,
    end_time TIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date (date),
    INDEX idx_platform (platform)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk pengeluaran
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    category ENUM('Bensin', 'Servis', 'Makan', 'Tagihan', 'Lainnya') DEFAULT 'Lainnya',
    description VARCHAR(255) DEFAULT '-',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date (date),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk target
CREATE TABLE IF NOT EXISTS targets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    target_type ENUM('harian', 'mingguan', 'bulanan') NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk tagihan
CREATE TABLE IF NOT EXISTS bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    due_date DATE NOT NULL,
    category ENUM('Listrik', 'Pulsa', 'Wifi', 'Cicilan', 'Lainnya') DEFAULT 'Lainnya',
    is_paid BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_due_date (due_date),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel untuk menyimpan pengaturan user
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value VARCHAR(100) NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO settings (setting_key, setting_value) VALUES
('perc_needs', '60'),
('perc_save', '30'),
('perc_emergency', '10'),
('daily_reminder', 'true'),
('target_harian', '200000')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Contoh data dummy
INSERT INTO entries (date, amount, note, platform, start_time, end_time) VALUES
('2024-11-01', 150000, 'Shift pagi', 'GrabBike', '08:00:00', '12:00:00'),
('2024-11-01', 80000, 'Bonus orderan jauh', 'GoRide', '12:30:00', '14:00:00'),
('2024-11-02', 200000, 'Shift siang + malam', 'ShopeeFood', '10:00:00', '18:00:00'),
('2024-11-03', 120000, 'Shift pagi', 'GrabFood', '07:00:00', '11:00:00'),
('2024-11-03', 50000, 'Order tambahan', 'GoFood', '11:30:00', '13:00:00');

INSERT INTO expenses (date, amount, category, description) VALUES
('2024-11-01', 25000, 'Bensin', 'Pertamax 3L'),
('2024-11-01', 15000, 'Makan', 'Makan siang'),
('2024-11-02', 30000, 'Bensin', 'Pertamax 4L'),
('2024-11-03', 20000, 'Makan', 'Sarapan');

INSERT INTO bills (name, amount, due_date, category) VALUES
('Listrik', 150000, '2024-11-05', 'Listrik'),
('Pulsa', 50000, '2024-11-10', 'Pulsa'),
('Cicilan Motor', 800000, '2024-11-15', 'Cicilan');

INSERT INTO targets (target_type, amount, start_date, is_active) VALUES
('harian', 200000, '2024-11-01', TRUE),
('mingguan', 1000000, '2024-11-01', TRUE),
('bulanan', 4000000, '2024-11-01', TRUE);