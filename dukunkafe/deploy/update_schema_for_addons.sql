
-- Dukun Cafe - POSRestoLite
-- Update schema for Add-ons feature

SET NAMES utf8mb4;
SET time_zone = '+07:00';
SET FOREIGN_KEY_CHECKS = 0;

--
-- Table structure for table `addon_groups`
--
DROP TABLE IF EXISTS `addon_groups`;
CREATE TABLE `addon_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `type` enum('radio','checkbox') NOT NULL DEFAULT 'checkbox',
  `required` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `addon_groups`
--
INSERT INTO `addon_groups` (`id`, `name`, `type`, `required`) VALUES
(1, 'Pilihan Susu', 'radio', 1),
(2, 'Topping', 'checkbox', 0),
(3, 'Sirup', 'checkbox', 0);

--
-- Table structure for table `addons`
--
DROP TABLE IF EXISTS `addons`;
CREATE TABLE `addons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `addon_group_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(15,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `addon_group_id` (`addon_group_id`),
  CONSTRAINT `addons_ibfk_1` FOREIGN KEY (`addon_group_id`) REFERENCES `addon_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `addons`
--
INSERT INTO `addons` (`id`, `addon_group_id`, `name`, `price`) VALUES
(1, 1, 'Susu Full Cream', 0.00),
(2, 1, 'Susu Skim', 0.00),
(3, 1, 'Susu Oat', 5000.00),
(4, 2, 'Boba', 4000.00),
(5, 2, 'Grass Jelly', 3000.00),
(6, 2, 'Pudding', 3000.00),
(7, 3, 'Sirup Caramel', 2000.00),
(8, 3, 'Sirup Hazelnut', 2000.00);

--
-- Table structure for table `menu_addon_groups`
--
DROP TABLE IF EXISTS `menu_addon_groups`;
CREATE TABLE `menu_addon_groups` (
  `menu_id` int(11) NOT NULL,
  `addon_group_id` int(11) NOT NULL,
  PRIMARY KEY (`menu_id`,`addon_group_id`),
  KEY `addon_group_id` (`addon_group_id`),
  CONSTRAINT `menu_addon_groups_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE,
  CONSTRAINT `menu_addon_groups_ibfk_2` FOREIGN KEY (`addon_group_id`) REFERENCES `addon_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menu_addon_groups`
--
INSERT INTO `menu_addon_groups` (`menu_id`, `addon_group_id`) VALUES
(1, 1),
(2, 1),
(15, 1),
(1, 2),
(2, 2),
(4, 2),
(5, 2),
(13, 2),
(14, 2),
(16, 2),
(1, 3),
(2, 3),
(15, 3);

--
-- Table structure for table `order_item_addons`
--
DROP TABLE IF EXISTS `order_item_addons`;
CREATE TABLE `order_item_addons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_item_id` int(11) NOT NULL,
  `addon_id` int(11) NOT NULL,
  `price` decimal(15,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_item_id` (`order_item_id`),
  KEY `addon_id` (`addon_id`),
  CONSTRAINT `order_item_addons_ibfk_1` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_item_addons_ibfk_2` FOREIGN KEY (`addon_id`) REFERENCES `addons` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Drop old tables
DROP TABLE IF EXISTS `order_item_mods`;
DROP TABLE IF EXISTS `modifiers`;

SET FOREIGN_KEY_CHECKS = 1;
