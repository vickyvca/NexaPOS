
<?php
require_once __DIR__ . '/../../../src/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $type = $_POST['type'] ?? '';

    $balance = (float)($_POST['balance'] ?? 0);

    if ($name && $type) {
        $pdo = get_pdo();
        $stmt = $pdo->prepare('INSERT INTO cash_accounts (name, type, balance) VALUES (?, ?, ?)');
        $stmt->execute([$name, $type, $balance]);
    }
}

header('Location: ' . base_url('admin/accounts.php'));
exit;
