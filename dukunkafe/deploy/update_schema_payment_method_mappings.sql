
CREATE TABLE `payment_method_mappings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_method` varchar(50) NOT NULL,
  `account_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_method` (`payment_method`),
  KEY `account_id` (`account_id`),
  CONSTRAINT `payment_method_mappings_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `cash_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
