CREATE TABLE `cash_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `cash_accounts` (`name`, `type`) VALUES
('Kas Tunai', 'cash'),
('BCA', 'bank'),
('Mandiri', 'bank');