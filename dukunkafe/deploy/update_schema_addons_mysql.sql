SET NAMES utf8mb4;
SET time_zone = '+07:00';
SET FOREIGN_KEY_CHECKS = 0;

-- Grup add-on
CREATE TABLE IF NOT EXISTS addon_groups (
  id INT(11) NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  type ENUM('radio','checkbox') NOT NULL DEFAULT 'checkbox',
  required TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Item add-on
CREATE TABLE IF NOT EXISTS addons (
  id INT(11) NOT NULL AUTO_INCREMENT,
  addon_group_id INT(11) NOT NULL,
  name VARCHAR(100) NOT NULL,
  price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (id),
  KEY addon_group_id (addon_group_id),
  CONSTRAINT addons_ibfk_1 FOREIGN KEY (addon_group_id) REFERENCES addon_groups (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Relasi menu ke grup add-on
CREATE TABLE IF NOT EXISTS menu_addon_groups (
  menu_id INT(11) NOT NULL,
  addon_group_id INT(11) NOT NULL,
  PRIMARY KEY (menu_id, addon_group_id),
  KEY addon_group_id (addon_group_id),
  CONSTRAINT menu_addon_groups_ibfk_1 FOREIGN KEY (menu_id) REFERENCES menus (id) ON DELETE CASCADE,
  CONSTRAINT menu_addon_groups_ibfk_2 FOREIGN KEY (addon_group_id) REFERENCES addon_groups (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add-on terpilih di item pesanan
CREATE TABLE IF NOT EXISTS order_item_addons (
  id INT(11) NOT NULL AUTO_INCREMENT,
  order_item_id INT(11) NOT NULL,
  addon_id INT(11) NOT NULL,
  price DECIMAL(15,2) NOT NULL,
  PRIMARY KEY (id),
  KEY order_item_id (order_item_id),
  KEY addon_id (addon_id),
  CONSTRAINT order_item_addons_ibfk_1 FOREIGN KEY (order_item_id) REFERENCES order_items (id) ON DELETE CASCADE,
  CONSTRAINT order_item_addons_ibfk_2 FOREIGN KEY (addon_id) REFERENCES addons (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- (Opsional) Jika sebelumnya ada tabel lama:
-- DROP TABLE IF EXISTS order_item_mods;
-- DROP TABLE IF EXISTS modifiers;

SET FOREIGN_KEY_CHECKS = 1;

