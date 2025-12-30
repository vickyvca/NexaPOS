SET NAMES utf8mb4;
SET time_zone = '+07:00';
SET FOREIGN_KEY_CHECKS = 0;

-- 1) Cabang (multi-branch)
CREATE TABLE IF NOT EXISTS branches (
  id INT NOT NULL AUTO_INCREMENT,
  code VARCHAR(20) NOT NULL,
  name VARCHAR(100) NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO branches (id, code, name, active)
VALUES (1, 'MAIN', 'Cabang Utama', 1);

-- 2) Tambah kolom branch_id di orders (jika belum ada)
-- Guard add orders.branch_id
SET @col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='orders' AND COLUMN_NAME='branch_id'
);
SET @sql := IF(@col=0,
  'ALTER TABLE orders ADD COLUMN branch_id INT NULL',
  'SELECT "orders.branch_id exists"'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Set nilai default untuk data lama
UPDATE orders SET branch_id = 1 WHERE branch_id IS NULL;

-- 3) Index untuk branch_id
-- Jalankan sekali; jika index sudah ada dan muncul error duplicate key, abaikan.
-- Guarded index creation for orders.branch_id
SET @idx_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'orders'
    AND INDEX_NAME = 'idx_orders_branch'
);
SET @sql := IF(@idx_exists = 0,
  'ALTER TABLE orders ADD INDEX idx_orders_branch (branch_id)',
  'SELECT "idx_orders_branch exists"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- (Opsional) Tambahkan FK (jalankan sekali jika perlu)
-- ALTER TABLE orders
--   ADD CONSTRAINT orders_ibfk_branch FOREIGN KEY (branch_id) REFERENCES branches (id);

-- 4) Waktu update status meja (untuk pelacakan durasi status)
-- Guard add tables.status_updated_at
SET @col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='tables' AND COLUMN_NAME='status_updated_at'
);
SET @sql := IF(@col=0,
  'ALTER TABLE tables ADD COLUMN status_updated_at DATETIME NULL DEFAULT NULL',
  'SELECT "tables.status_updated_at exists"'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 5) Kolom HPP untuk mode inventaris Sederhana
-- Guard add menus.hpp
SET @col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='menus' AND COLUMN_NAME='hpp'
);
SET @sql := IF(@col=0,
  'ALTER TABLE menus ADD COLUMN hpp DECIMAL(15,2) NOT NULL DEFAULT 0',
  'SELECT "menus.hpp exists"'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- 6) Cabang untuk meja (tables)
-- Guard add tables.branch_id
SET @col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='tables' AND COLUMN_NAME='branch_id'
);
SET @sql := IF(@col=0,
  'ALTER TABLE tables ADD COLUMN branch_id INT NULL',
  'SELECT "tables.branch_id exists"'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
UPDATE tables SET branch_id = 1 WHERE branch_id IS NULL;
-- Guarded index creation for tables.branch_id
SET @idx_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tables'
    AND INDEX_NAME = 'idx_tables_branch'
);
SET @sql := IF(@idx_exists = 0,
  'ALTER TABLE tables ADD INDEX idx_tables_branch (branch_id)',
  'SELECT "idx_tables_branch exists"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
-- (Opsional) Tambahkan FK sekali jika perlu
-- ALTER TABLE tables
--   ADD CONSTRAINT tables_ibfk_branch FOREIGN KEY (branch_id) REFERENCES branches (id);

SET FOREIGN_KEY_CHECKS = 1;

-- 7) Cabang untuk user (proteksi akses cabang)
-- Guard add users.branch_id
SET @col := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='branch_id'
);
SET @sql := IF(@col=0,
  'ALTER TABLE users ADD COLUMN branch_id INT NULL',
  'SELECT "users.branch_id exists"'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
UPDATE users SET branch_id = 1 WHERE branch_id IS NULL;
-- Guarded index creation for users.branch_id
SET @idx_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'users'
    AND INDEX_NAME = 'idx_users_branch'
);
SET @sql := IF(@idx_exists = 0,
  'ALTER TABLE users ADD INDEX idx_users_branch (branch_id)',
  'SELECT "idx_users_branch exists"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
-- (Opsional) Tambahkan FK sekali jika perlu
-- ALTER TABLE users
--   ADD CONSTRAINT users_ibfk_branch FOREIGN KEY (branch_id) REFERENCES branches (id);
