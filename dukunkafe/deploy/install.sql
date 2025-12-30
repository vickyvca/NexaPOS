
-- Dukun Cafe - POSRestoLite
-- Database Installation Script
-- Target: MySQL / MariaDB
-- Version: 1.0

SET NAMES utf8mb4;
SET time_zone = '+07:00';
SET FOREIGN_KEY_CHECKS = 0;

--
-- Table structure for table `accounts`
--
DROP TABLE IF EXISTS `accounts`;
CREATE TABLE `accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('ASSET','LIABILITY','EQUITY','REVENUE','EXPENSE') NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `accounts`
--
INSERT INTO `accounts` (`id`, `code`, `name`, `type`, `active`) VALUES
(1, '1-10100', 'Kas Toko', 'ASSET', 1),
(2, '1-10200', 'Bank BCA', 'ASSET', 1),
(3, '4-10100', 'Penjualan Makanan & Minuman', 'REVENUE', 1),
(4, '5-10100', 'HPP (Harga Pokok Penjualan)', 'EXPENSE', 1),
(5, '1-10300', 'Persediaan Bahan Baku', 'ASSET', 1),
(6, '2-10100', 'Hutang Supplier', 'LIABILITY', 1),
(7, '5-20100', 'Beban Gaji', 'EXPENSE', 1),
(8, '5-20200', 'Beban Listrik & Air', 'EXPENSE', 1),
(9, '5-20900', 'Beban Operasional Lainnya', 'EXPENSE', 1),
(10, '4-20100', 'Diskon Penjualan', 'REVENUE', 1),
(11, '2-20100', 'Pajak Keluaran (PPN)', 'LIABILITY', 1);

--
-- Table structure for table `attendances`
--
DROP TABLE IF EXISTS `attendances`;
CREATE TABLE `attendances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `type` enum('IN','OUT') NOT NULL,
  `ts` datetime NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_id` (`employee_id`),
  KEY `ts` (`ts`),
  CONSTRAINT `attendances_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `cash_sessions`
--
DROP TABLE IF EXISTS `cash_sessions`;
CREATE TABLE `cash_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `opened_at` datetime NOT NULL,
  `closed_at` datetime DEFAULT NULL,
  `opening_cash` decimal(15,2) NOT NULL,
  `closing_cash` decimal(15,2) DEFAULT NULL,
  `variance` decimal(15,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `cash_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `categories`
--
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--
INSERT INTO `categories` (`id`, `name`, `parent_id`) VALUES
(1, 'Makanan', NULL),
(2, 'Minuman', NULL),
(3, 'Cemilan', NULL),
(4, 'Kopi', 2),
(5, 'Teh', 2),
(6, 'Jus', 2),
(7, 'Makanan Berat', 1),
(8, 'Pasta', 7),
(9, 'Nasi', 7),
(10, 'Steak', 7),
(11, 'Kue', 3),
(12, 'Gorengan', 3);

--
-- Table structure for table `employees`
--
DROP TABLE IF EXISTS `employees`;
CREATE TABLE `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nik` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `pin` varchar(255) NOT NULL COMMENT 'Hashed PIN',
  `role_hint` enum('admin','kasir','waiter','kitchen','manager','hr') NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nik` (`nik`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--
INSERT INTO `employees` (`id`, `nik`, `name`, `pin`, `role_hint`, `active`, `created_at`) VALUES
(1, 'EMP-001', 'Budi Waiter', '1234', 'waiter', 1, '2025-10-17 03:00:00'),
(2, 'EMP-002', 'Cindy Kasir', '5678', 'kasir', 1, '2025-10-17 03:00:00'),
(3, 'EMP-003', 'Dodi Kitchen', '1122', 'kitchen', 1, '2025-10-17 03:00:00');

--
-- Table structure for table `expenses`
--
DROP TABLE IF EXISTS `expenses`;
CREATE TABLE `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cash_session_id` int(11) DEFAULT NULL,
  `account_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `memo` varchar(255) NOT NULL,
  `paid_via` enum('CASH','BANK') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `cash_session_id` (`cash_session_id`),
  KEY `account_id` (`account_id`),
  CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`cash_session_id`) REFERENCES `cash_sessions` (`id`),
  CONSTRAINT `expenses_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `journals`
--
DROP TABLE IF EXISTS `journals`;
CREATE TABLE `journals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `ref_type` varchar(50) DEFAULT NULL,
  `ref_id` int(11) DEFAULT NULL,
  `memo` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `journal_lines`
--
DROP TABLE IF EXISTS `journal_lines`;
CREATE TABLE `journal_lines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `journal_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `debit` decimal(15,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(15,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `journal_id` (`journal_id`),
  KEY `account_id` (`account_id`),
  CONSTRAINT `journal_lines_ibfk_1` FOREIGN KEY (`journal_id`) REFERENCES `journals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `journal_lines_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `materials`
--
DROP TABLE IF EXISTS `materials`;
CREATE TABLE `materials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `uom` varchar(20) NOT NULL COMMENT 'unit of measure',
  `min_stock` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_semi_finished` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `materials`
--
INSERT INTO `materials` (`id`, `code`, `name`, `uom`, `min_stock`, `is_semi_finished`, `active`) VALUES
(1, 'MT-001', 'Biji Kopi Arabika', 'gram', 500.00, 0, 1),
(2, 'MT-002', 'Susu UHT', 'ml', 1000.00, 0, 1),
(3, 'MT-003', 'Gula Pasir', 'gram', 1000.00, 0, 1),
(4, 'MT-004', 'Daging Sapi Sirloin', 'gram', 2000.00, 0, 1),
(5, 'MT-005', 'Kentang', 'gram', 5000.00, 0, 1),
(6, 'MT-006', 'Spaghetti', 'gram', 1000.00, 0, 1),
(7, 'MT-007', 'Saus Bolognese', 'gram', 500.00, 1, 1),
(8, 'MT-008', 'Daun Teh', 'gram', 200.00, 0, 1),
(9, 'MT-009', 'Tepung Terigu', 'gram', 1000.00, 0, 1),
(10, 'MT-010', 'Telur', 'pcs', 20.00, 0, 1),
(11, 'MT-011', 'Minyak Goreng', 'ml', 2000.00, 0, 1),
(12, 'MT-012', 'Bawang Putih', 'gram', 100.00, 0, 1),
(13, 'MT-013', 'Dada Ayam', 'gram', 2000.00, 0, 1),
(14, 'MT-014', 'Nasi Putih', 'gram', 10000.00, 1, 1),
(15, 'MT-015', 'Sirup Gula Aren', 'ml', 500.00, 1, 1);

--
-- Table structure for table `menus`
--
DROP TABLE IF EXISTS `menus`;
CREATE TABLE `menus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sku` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category_id` int(11) NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `print_station` enum('HOT','GRILL','DRINK','PASTRY') NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `menus_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menus`
--
INSERT INTO `menus` (`id`, `sku`, `name`, `category_id`, `price`, `is_active`, `print_station`) VALUES
(1, 'CF-001', 'Caffe Latte', 4, 25000.00, 1, 'DRINK'),
(2, 'CF-002', 'Kopi Gula Aren', 4, 22000.00, 1, 'DRINK'),
(3, 'CF-003', 'Americano', 4, 20000.00, 1, 'DRINK'),
(4, 'TH-001', 'Es Teh Manis', 5, 10000.00, 1, 'DRINK'),
(5, 'TH-002', 'Lemon Tea', 5, 15000.00, 1, 'DRINK'),
(6, 'FD-001', 'Nasi Goreng Spesial', 9, 35000.00, 1, 'HOT'),
(7, 'FD-002', 'Spaghetti Bolognese', 8, 45000.00, 1, 'HOT'),
(8, 'FD-003', 'Sirloin Steak', 10, 125000.00, 1, 'GRILL'),
(9, 'FD-004', 'Chicken Katsu', 7, 40000.00, 1, 'HOT'),
(10, 'SN-001', 'Kentang Goreng', 12, 20000.00, 1, 'HOT'),
(11, 'SN-002', 'Pisang Goreng', 12, 18000.00, 1, 'HOT'),
(12, 'SN-003', 'Red Velvet Cake', 11, 30000.00, 1, 'PASTRY'),
(13, 'JS-001', 'Jus Alpukat', 6, 25000.00, 1, 'DRINK'),
(14, 'JS-002', 'Jus Jeruk', 6, 22000.00, 1, 'DRINK'),
(15, 'CF-004', 'Cappuccino', 4, 25000.00, 1, 'DRINK'),
(16, 'TH-003', 'Thai Tea', 5, 18000.00, 1, 'DRINK'),
(17, 'FD-005', 'Mie Goreng', 9, 30000.00, 1, 'HOT'),
(18, 'FD-006', 'Aglio Olio', 8, 42000.00, 1, 'HOT'),
(19, 'FD-007', 'Rib Eye Steak', 10, 150000.00, 1, 'GRILL'),
(20, 'FD-008', 'Chicken Cordon Bleu', 7, 55000.00, 1, 'HOT'),
(21, 'SN-004', 'Onion Rings', 12, 22000.00, 1, 'HOT'),
(22, 'SN-005', 'Tahu Crispy', 12, 15000.00, 1, 'HOT'),
(23, 'SN-006', 'Cheesecake', 11, 35000.00, 1, 'PASTRY'),
(24, 'JS-003', 'Jus Mangga', 6, 25000.00, 1, 'DRINK'),
(25, 'JS-004', 'Jus Strawberry', 6, 25000.00, 1, 'DRINK'),
(26, 'FD-009', 'Nasi Ayam Geprek', 9, 28000.00, 1, 'HOT'),
(27, 'FD-010', 'Carbonara', 8, 48000.00, 1, 'HOT'),
(28, 'FD-011', 'Tenderloin Steak', 10, 140000.00, 1, 'GRILL'),
(29, 'SN-007', 'Cireng', 12, 12000.00, 1, 'HOT'),
(30, 'SN-008', 'Croissant', 11, 20000.00, 1, 'PASTRY');

--
-- Table structure for table `menu_bom`
--
DROP TABLE IF EXISTS `menu_bom`;
CREATE TABLE `menu_bom` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `qty` decimal(10,2) NOT NULL,
  `uom` varchar(20) NOT NULL,
  `yield_pct` decimal(5,2) NOT NULL DEFAULT 100.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `menu_material` (`menu_id`,`material_id`),
  KEY `material_id` (`material_id`),
  CONSTRAINT `menu_bom_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE,
  CONSTRAINT `menu_bom_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_bom`
--
INSERT INTO `menu_bom` (`id`, `menu_id`, `material_id`, `qty`, `uom`, `yield_pct`) VALUES
(1, 1, 1, 18.00, 'gram', 100.00),
(2, 1, 2, 150.00, 'ml', 100.00),
(3, 7, 6, 80.00, 'gram', 100.00),
(4, 7, 7, 100.00, 'gram', 100.00),
(5, 8, 4, 200.00, 'gram', 100.00),
(6, 8, 5, 150.00, 'gram', 85.00);

--
-- Table structure for table `modifiers`
--
DROP TABLE IF EXISTS `modifiers`;
CREATE TABLE `modifiers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price_delta` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `menu_id` (`menu_id`),
  CONSTRAINT `modifiers_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `orders`
--
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_no` varchar(20) NOT NULL,
  `table_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `pax` int(11) NOT NULL DEFAULT 1,
  `channel` enum('DINE_IN','TAKE_AWAY','DELIVERY') NOT NULL,
  `status` enum('OPEN','CLOSED','CANCELED') NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `discount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(15,2) NOT NULL DEFAULT 0.00,
  `service` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total` decimal(15,2) NOT NULL,
  `paid_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `closed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`),
  KEY `table_id` (`table_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `order_items`
--
DROP TABLE IF EXISTS `order_items`;
CREATE TABLE `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `menu_id` int(11) NOT NULL,
  `qty` decimal(10,2) NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `discount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notes` varchar(255) DEFAULT NULL,
  `status` enum('QUEUED','IN_PROGRESS','READY','SERVED','DONE','CANCELED') NOT NULL,
  `started_at` datetime DEFAULT NULL,
  `ready_at` datetime DEFAULT NULL,
  `served_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `menu_id` (`menu_id`),
  KEY `status` (`status`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `order_item_mods`
--
DROP TABLE IF EXISTS `order_item_mods`;
CREATE TABLE `order_item_mods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_item_id` int(11) NOT NULL,
  `modifier_id` int(11) NOT NULL,
  `price_delta` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_item_id` (`order_item_id`),
  KEY `modifier_id` (`modifier_id`),
  CONSTRAINT `order_item_mods_ibfk_1` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_item_mods_ibfk_2` FOREIGN KEY (`modifier_id`) REFERENCES `modifiers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `purchases`
--
DROP TABLE IF EXISTS `purchases`;
CREATE TABLE `purchases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `po_no` varchar(20) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `status` enum('DRAFT','ORDERED','RECEIVED','CANCELED') NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `discount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `tax` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total` decimal(15,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `received_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `po_no` (`po_no`),
  KEY `supplier_id` (`supplier_id`),
  CONSTRAINT `purchases_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `purchase_items`
--
DROP TABLE IF EXISTS `purchase_items`;
CREATE TABLE `purchase_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `purchase_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL,
  `qty` decimal(10,2) NOT NULL,
  `uom` varchar(20) NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `discount` decimal(15,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `purchase_id` (`purchase_id`),
  KEY `material_id` (`material_id`),
  CONSTRAINT `purchase_items_ibfk_1` FOREIGN KEY (`purchase_id`) REFERENCES `purchases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_items_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `settings`
--
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(50) NOT NULL,
  `value` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--
INSERT INTO `settings` (`id`, `key`, `value`) VALUES
(1, 'tax_pct', '10'),
(2, 'service_pct', '5'),
(3, 'currency_symbol', 'Rp'),
(4, 'qris_merchant_name', 'Dukun Cafe');

--
-- Table structure for table `shifts`
--
DROP TABLE IF EXISTS `shifts`;
CREATE TABLE `shifts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `time_in` time NOT NULL,
  `time_out` time NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `stock_cards`
--
DROP TABLE IF EXISTS `stock_cards`;
CREATE TABLE `stock_cards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `material_id` int(11) NOT NULL,
  `qty_on_hand` decimal(15,2) NOT NULL,
  `uom` varchar(20) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `material_id` (`material_id`),
  CONSTRAINT `stock_cards_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `stock_moves`
--
DROP TABLE IF EXISTS `stock_moves`;
CREATE TABLE `stock_moves` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `material_id` int(11) NOT NULL,
  `move_type` enum('IN','OUT','ADJUST') NOT NULL,
  `qty` decimal(15,2) NOT NULL,
  `uom` varchar(20) NOT NULL,
  `ref_type` varchar(50) DEFAULT NULL,
  `ref_id` int(11) DEFAULT NULL,
  `unit_cost` decimal(15,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `material_id` (`material_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `stock_moves_ibfk_1` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `suppliers`
--
DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `tables`
--
DROP TABLE IF EXISTS `tables`;
CREATE TABLE `tables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(50) NOT NULL,
  `area` varchar(50) DEFAULT NULL,
  `capacity` int(11) NOT NULL,
  `status` enum('AVAILABLE','OCCUPIED','ORDERING','COOKING','READY','SERVED','CLEANING') NOT NULL DEFAULT 'AVAILABLE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tables`
--
INSERT INTO `tables` (`id`, `code`, `name`, `area`, `capacity`, `status`, `created_at`) VALUES
(1, 'T01', 'Table 1', 'Indoor', 4, 'AVAILABLE', '2025-10-17 03:00:00'),
(2, 'T02', 'Table 2', 'Indoor', 4, 'AVAILABLE', '2025-10-17 03:00:00'),
(3, 'T03', 'Table 3', 'Indoor', 2, 'AVAILABLE', '2025-10-17 03:00:00'),
(4, 'T04', 'Table 4', 'Indoor', 2, 'AVAILABLE', '2025-10-17 03:00:00'),
(5, 'T05', 'Table 5', 'Indoor', 6, 'AVAILABLE', '2025-10-17 03:00:00'),
(6, 'V01', 'VIP 1', 'VIP Room', 8, 'AVAILABLE', '2025-10-17 03:00:00'),
(7, 'O01', 'Outdoor 1', 'Outdoor', 4, 'AVAILABLE', '2025-10-17 03:00:00'),
(8, 'O02', 'Outdoor 2', 'Outdoor', 4, 'AVAILABLE', '2025-10-17 03:00:00'),
(9, 'O03', 'Outdoor 3', 'Outdoor', 2, 'AVAILABLE', '2025-10-17 03:00:00'),
(10, 'B01', 'Bar Stool 1', 'Bar', 1, 'AVAILABLE', '2025-10-17 03:00:00');

--
-- Table structure for table `users`
--
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','kasir','waiter','kitchen','manager','hr') NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `active`, `created_at`) VALUES
(1, 'Admin Demo', 'admin@demo.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, '2025-10-17 03:00:00'),
(2, 'Kasir Demo', 'kasir@demo.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kasir', 1, '2025-10-17 03:00:00'),
(3, 'Kitchen Demo', 'kitchen@demo.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kitchen', 1, '2025-10-17 03:00:00'),
(4, 'Manager Demo', 'manager@demo.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 1, '2025-10-17 03:00:00'),
(5, 'HR Demo', 'hr@demo.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'hr', 1, '2025-10-17 03:00:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendances`
--
ALTER TABLE `attendances`
  ADD KEY `idx_employee_ts` (`employee_id`,`ts`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD KEY `idx_status_created` (`status`,`created_at`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD KEY `idx_status_menu` (`status`,`menu_id`);

--
-- Indexes for table `stock_moves`
--
ALTER TABLE `stock_moves`
  ADD KEY `idx_material_created` (`material_id`,`created_at`);


SET FOREIGN_KEY_CHECKS = 1;
