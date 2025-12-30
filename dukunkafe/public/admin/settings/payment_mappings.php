
<?php
require_once __DIR__ . '/../../../src/bootstrap.php';

require_auth(['admin', 'manager']);

$title = 'Pemetaan Metode Pembayaran';
$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mappings = $_POST['mappings'] ?? [];
    $stmt = $pdo->prepare("REPLACE INTO payment_method_mappings (payment_method, account_id) VALUES (?, ?)");
    foreach ($mappings as $method => $account_id) {
        if (!empty($account_id)) {
            $stmt->execute([$method, $account_id]);
        }
    }
    // Redirect back to the canonical route (without .php) for consistency
    redirect(base_url('admin/settings/payment_mappings'));
}

$accounts = $pdo->query("SELECT * FROM cash_accounts ORDER BY name")->fetchAll();
$mappings_stmt = $pdo->query("SELECT * FROM payment_method_mappings");
$mappings = [];
while ($row = $mappings_stmt->fetch()) {
    $mappings[$row['payment_method']] = $row['account_id'];
}

$payment_methods = ['CASH', 'QRIS', 'CARD'];

view('admin/settings/payment_mappings', compact('title', 'accounts', 'mappings', 'payment_methods'));
