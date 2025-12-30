CREATE TABLE `settings` (
  `key` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
-- Multi-branch support
CREATE TABLE IF NOT EXISTS `branches` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(20) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `branches` (`id`, `code`, `name`, `active`) VALUES (1, 'MAIN', 'Cabang Utama', 1);

-- Add branch_id to orders
ALTER TABLE `orders` ADD COLUMN `branch_id` INT NULL;
UPDATE `orders` SET `branch_id` = 1 WHERE `branch_id` IS NULL;
ALTER TABLE `orders` ADD KEY `branch_id` (`branch_id`);
ALTER TABLE `orders` ADD CONSTRAINT `orders_ibfk_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`);
