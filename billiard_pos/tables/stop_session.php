<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/lamp_control.php';
check_login();
header('Content-Type: application/json');

$table_id = (int)($_POST['table_id'] ?? 0);
$session_id = (int)($_POST['session_id'] ?? 0);

if (!$table_id && !$session_id) {
    echo json_encode(['status' => 'error', 'message' => 'Parameter kurang']);
    exit;
}

if ($session_id) {
    $stmt = $pdo->prepare("SELECT s.*, t.controller_ip, t.relay_channel FROM sessions s JOIN billiard_tables t ON s.table_id = t.id WHERE s.id = ? AND s.status = 'running' LIMIT 1");
    $stmt->execute([$session_id]);
} else {
    $stmt = $pdo->prepare("SELECT s.*, t.controller_ip, t.relay_channel FROM sessions s JOIN billiard_tables t ON s.table_id = t.id WHERE s.table_id = ? AND s.status = 'running' LIMIT 1");
    $stmt->execute([$table_id]);
}
$session = $stmt->fetch();
if (!$session) {
    echo json_encode(['status' => 'error', 'message' => 'Session tidak ditemukan']);
    exit;
}

$tariffStmt = $pdo->prepare("SELECT * FROM tariffs WHERE id = ?");
$tariffStmt->execute([$session['tariff_id']]);
$tariff = $tariffStmt->fetch();
$package = get_package($pdo, $session['package_id']);

$calc = calculate_billing_with_package($session['start_time'], $tariff['rate_per_hour'], $tariff['min_minutes'], $package, $tariff);
$carry_minutes = (int)($session['total_minutes'] ?? 0);
$carry_amount  = (int)($session['total_amount'] ?? 0);
$minutes = $carry_minutes + $calc['minutes'];
$total_amount = $carry_amount + $calc['amount'];

$upd = $pdo->prepare("UPDATE sessions SET end_time = NOW(), total_minutes = ?, total_amount = ?, status = 'finished' WHERE id = ?");
try {
    $upd->execute([$minutes, $total_amount, $session['id']]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()]);
    exit;
}

$pdo->prepare("UPDATE billiard_tables SET status = 'idle' WHERE id = ?")->execute([$session['table_id']]);

if (!empty($session['controller_ip']) && !empty($session['relay_channel'])) {
    call_lamp($session['controller_ip'], $session['relay_channel'], 'off');
}

$settings = load_company_settings();
if (!empty($settings['fonnte_target'])) {
    // Ambil POS pending terkait session/table
    $posPending = $pdo->prepare("SELECT o.id, o.subtotal FROM orders o WHERE o.is_paid = 0 AND ((o.session_id = ?) OR (o.session_id IS NULL AND o.table_id = ?))");
    $posPending->execute([$session['id'], $session['table_id']]);
    $pendingOrders = $posPending->fetchAll();
    $pendingTotal = 0;
    $pendingList = [];
    if ($pendingOrders) {
        $ids = array_column($pendingOrders, 'id');
        $in = implode(',', array_fill(0, count($ids), '?'));
        $itemsStmt = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id IN ($in)");
        $itemsStmt->execute($ids);
        foreach ($pendingOrders as $po) {
            $pendingTotal += (int)$po['subtotal'];
        }
        foreach ($itemsStmt as $it) {
            $pendingList[] = ($it['name'] ?: 'Item #'.$it['product_id']) . ' x' . $it['qty'] . ' (' . format_rupiah($it['subtotal']) . ')';
        }
    }
$pkg = $package ? $package['name'] : '-';
$grand = $total_amount + $pendingTotal;
$kasir = $_SESSION['user']['username'] ?? '';
$msg = "Sesi selesai\nMeja: {$session['table_id']}\nCustomer: {$session['customer_name']}\nKasir: {$kasir}\nDurasi: " . human_duration($minutes) . "\nBilling: " . format_rupiah($total_amount) . "\nPaket: {$pkg}\nPOS (pending): " . format_rupiah($pendingTotal);
if ($pendingList) {
    $msg .= "\nDetail POS: " . implode(', ', $pendingList);
}
$msg .= "\n*Grand Total*: " . format_rupiah($grand);
send_fonnte_notification($settings['fonnte_target'], $msg);
}

echo json_encode([
    'status' => 'ok',
    'table_id' => $session['table_id'],
    'session_id' => $session['id'],
    'total_minutes' => $minutes,
    'total_amount' => $total_amount
]);
?>
