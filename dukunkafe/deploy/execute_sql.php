<?php
$config = require __DIR__ . '/../config.php';

$db = $config['database'];

$pdo = new PDO(
    "mysql:host={$db['host']};port={$db['port']};dbname={$db['dbname']};charset={$db['charset']}",
    $db['user'],
    $db['password']
);

$pdo->exec('SET FOREIGN_KEY_CHECKS=0;');

$sql = "DROP TABLE IF EXISTS `cash_accounts`;";
$pdo->exec($sql);

$sql = file_get_contents(__DIR__ . '/update_schema_cash_accounts.sql');
$pdo->exec($sql);

$sql = "DROP TABLE IF EXISTS `cash_transactions`;";
$pdo->exec($sql);

$sql = file_get_contents(__DIR__ . '/update_schema_cash_transactions.sql');
$pdo->exec($sql);

$sql = "DROP TABLE IF EXISTS `payment_method_mappings`;";
$pdo->exec($sql);

$sql = file_get_contents(__DIR__ . '/update_schema_payment_method_mappings.sql');
$pdo->exec($sql);

$pdo->exec('SET FOREIGN_key_checks=1;');

echo "SQL script executed successfully.";