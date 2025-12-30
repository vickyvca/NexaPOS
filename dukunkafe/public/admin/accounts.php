
<?php
require_once __DIR__ . '/../../src/bootstrap.php';

$pdo = get_pdo();
$accounts = $pdo->query('SELECT * FROM cash_accounts ORDER BY name')->fetchAll();

$title = 'Kelola Akun';
$viewPath = __DIR__ . '/views/accounts.view.php';

include __DIR__ . '/../../templates/layout.php';
