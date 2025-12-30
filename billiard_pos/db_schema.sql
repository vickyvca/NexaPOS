-- SQL schema for Billiard POS
-- Import this file into MySQL/MariaDB

-- CREATE DATABASE billiard_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE billiard_pos;

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','kasir') NOT NULL DEFAULT 'kasir',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) UNIQUE NOT NULL,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(50) UNIQUE NOT NULL,
  discount_percent INT NOT NULL DEFAULT 0,
  points INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS maintenance_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  table_id INT NOT NULL,
  operator_id INT NOT NULL,
  start_time DATETIME NOT NULL,
  end_time DATETIME DEFAULT NULL,
  duration_minutes INT NOT NULL DEFAULT 5,
  note VARCHAR(200) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_maint_table FOREIGN KEY (table_id) REFERENCES billiard_tables(id),
  CONSTRAINT fk_maint_user FOREIGN KEY (operator_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS accounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  type ENUM('cash','bank','qris','card','other') NOT NULL DEFAULT 'cash',
  note VARCHAR(200),
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS journals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  account_id INT NOT NULL,
  txn_time DATETIME NOT NULL,
  type ENUM('in','out') NOT NULL,
  amount INT NOT NULL,
  description VARCHAR(200),
  ref_type VARCHAR(50),
  ref_id INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_journal_account FOREIGN KEY (account_id) REFERENCES accounts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expenses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  account_id INT NOT NULL,
  category VARCHAR(100) NOT NULL,
  description VARCHAR(200),
  amount INT NOT NULL,
  expense_time DATETIME NOT NULL,
  operator_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_expense_account FOREIGN KEY (account_id) REFERENCES accounts(id),
  CONSTRAINT fk_expense_user FOREIGN KEY (operator_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS packages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  duration_minutes INT NOT NULL,
  special_price INT NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS billiard_tables (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  controller_ip VARCHAR(50) DEFAULT NULL,
  relay_channel INT DEFAULT NULL,
  status ENUM('idle','running','paused','maintenance') NOT NULL DEFAULT 'idle',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tariffs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  rate_per_hour INT NOT NULL DEFAULT 0,
  min_minutes INT NOT NULL DEFAULT 0,
  table_id INT DEFAULT NULL, -- legacy single table (opsional)
  table_ids VARCHAR(255) DEFAULT NULL, -- daftar meja CSV, misal "1,2,3"
  day_of_week TINYINT DEFAULT NULL, -- 0=Sunday, 6=Saturday
  start_time TIME DEFAULT NULL,
  end_time TIME DEFAULT NULL,
  block_minutes INT DEFAULT NULL,
  block_price INT DEFAULT NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tariffs_table FOREIGN KEY (table_id) REFERENCES billiard_tables(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  table_id INT NOT NULL,
  tariff_id INT NOT NULL,
  operator_id INT NOT NULL,
  member_id INT DEFAULT NULL,
  package_id INT DEFAULT NULL,
  customer_name VARCHAR(150) NOT NULL,
  customer_phone VARCHAR(30) NOT NULL,
  start_time DATETIME NOT NULL,
  end_time DATETIME DEFAULT NULL,
  total_minutes INT DEFAULT 0,
  total_amount INT DEFAULT 0,
  status ENUM('running','paused','finished') NOT NULL DEFAULT 'running',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sessions_table FOREIGN KEY (table_id) REFERENCES billiard_tables(id),
  CONSTRAINT fk_sessions_tariff FOREIGN KEY (tariff_id) REFERENCES tariffs(id),
  CONSTRAINT fk_sessions_user FOREIGN KEY (operator_id) REFERENCES users(id),
  CONSTRAINT fk_sessions_member FOREIGN KEY (member_id) REFERENCES members(id),
  CONSTRAINT fk_sessions_package FOREIGN KEY (package_id) REFERENCES packages(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  category VARCHAR(50) NOT NULL,
  price INT NOT NULL DEFAULT 0,
  stock INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS purchases (
  id INT AUTO_INCREMENT PRIMARY KEY,
  supplier VARCHAR(150) DEFAULT NULL,
  note TEXT,
  total INT NOT NULL DEFAULT 0,
  operator_id INT NOT NULL,
  purchase_time DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_purchases_user FOREIGN KEY (operator_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS purchase_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  purchase_id INT NOT NULL,
  product_id INT NOT NULL,
  qty INT NOT NULL DEFAULT 0,
  cost_price INT NOT NULL DEFAULT 0,
  subtotal INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_purchase_items_p FOREIGN KEY (purchase_id) REFERENCES purchases(id),
  CONSTRAINT fk_purchase_items_prod FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  table_id INT DEFAULT NULL,
  session_id INT DEFAULT NULL,
  member_id INT DEFAULT NULL,
  customer_name VARCHAR(150) DEFAULT NULL,
  customer_phone VARCHAR(30) DEFAULT NULL,
  operator_id INT NOT NULL,
  order_time DATETIME NOT NULL,
  total_items INT NOT NULL DEFAULT 0,
  subtotal INT NOT NULL DEFAULT 0,
  grand_total INT NOT NULL DEFAULT 0,
  discount_amount INT NOT NULL DEFAULT 0,
  extra_charge_amount INT NOT NULL DEFAULT 0,
  extra_charge_note VARCHAR(200) DEFAULT NULL,
  payment_amount INT NOT NULL DEFAULT 0,
  payment_method ENUM('cash','transfer','qris') DEFAULT 'cash',
  change_amount INT NOT NULL DEFAULT 0,
  is_paid TINYINT(1) NOT NULL DEFAULT 1,
  points_earned INT NOT NULL DEFAULT 0,
  note TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_orders_member FOREIGN KEY (member_id) REFERENCES members(id),
  CONSTRAINT fk_orders_table FOREIGN KEY (table_id) REFERENCES billiard_tables(id),
  CONSTRAINT fk_orders_session FOREIGN KEY (session_id) REFERENCES sessions(id),
  CONSTRAINT fk_orders_user FOREIGN KEY (operator_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_id INT NOT NULL,
  qty INT NOT NULL DEFAULT 1,
  price INT NOT NULL DEFAULT 0,
  subtotal INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id),
  CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS shift_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  shift_name VARCHAR(50) NOT NULL,
  start_time DATETIME NOT NULL,
  end_time DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_shift_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample data
INSERT INTO users (username, password_hash, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('kasir', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kasir');

INSERT INTO billiard_tables (name, controller_ip, relay_channel, status) VALUES
('Meja 1', '192.168.0.50', 1, 'idle'),
('Meja 2', '192.168.0.50', 2, 'idle'),
('VIP 1', '192.168.0.51', 1, 'idle');

INSERT INTO members (code, name, phone, discount_percent, points, is_active) VALUES
('MBR001', 'Member Gold', '081234567890', 10, 0, 1),
('MBR002', 'Member Silver', '089876543210', 5, 0, 1);

INSERT INTO packages (name, duration_minutes, special_price, is_active) VALUES
('Paket 2 Jam', 120, 70000, 1),
('Paket 3 Jam', 180, 95000, 1);

INSERT INTO accounts (name, type, note) VALUES
('Kas Besar', 'cash', 'Default kas'),
('Bank Transfer', 'bank', 'Pembayaran transfer/card'),
('QRIS', 'qris', 'Pembayaran QRIS');

INSERT INTO tariffs (name, rate_per_hour, min_minutes, table_id, day_of_week, start_time, end_time, is_default) VALUES
('Normal', 40000, 30, NULL, NULL, NULL, NULL, 1),
('Malam', 50000, 30, NULL, 5, '18:00:00', '23:59:59', 0),
('Weekend', 60000, 30, NULL, 6, NULL, NULL, 0);

INSERT INTO products (name, category, price, is_active) VALUES
('Teh Botol', 'minuman', 8000, 1),
('Air Mineral', 'minuman', 6000, 1),
('Kopi Susu', 'minuman', 12000, 1),
('Indomie Goreng', 'makanan', 15000, 1),
('Kentang Goreng', 'snack', 18000, 1),
('Keripik', 'snack', 10000, 1);
