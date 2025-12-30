<?php
require_once __DIR__ . '/../includes/functions.php';
check_login();

$order_id = (int)($_GET['order_id'] ?? 0);
$table_id = (int)($_GET['table_id'] ?? 0);
$redirect = $table_id ? "/billiard_pos/pos/pos.php?table_id={$table_id}" : "/billiard_pos/pos/pos.php";

if (!$order_id) {
    $_SESSION['flash_error'] = 'Order_id wajib.';
    header("Location: {$redirect}");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
$stmt->execute([$order_id]);
$order = $stmt->fetch();
if (!$order) {
    $_SESSION['flash_error'] = 'Order tidak ditemukan.';
    header("Location: {$redirect}");
    exit;
}
if ((int)$order['is_paid'] === 3) {
    $_SESSION['flash_error'] = 'Order sudah dibatalkan.';
    header("Location: {$redirect}");
    exit;
}

// Ambil item untuk kembalikan stok
$items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items->execute([$order_id]);
$items = $items->fetchAll();

$pdo->beginTransaction();
try {
    // kembalikan stok
    if ($items) {
        $st = $pdo->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
        foreach ($items as $it) {
            $st->execute([$it['qty'], $it['product_id']]);
        }
    }
    // hapus jurnal terkait order ini
    $pdo->prepare("DELETE FROM journals WHERE ref_type = 'order' AND ref_id = ?")->execute([$order_id]);
    // tandai order void
    $note = trim(($order['note'] ?? '') . ' [void]');
    $pdo->prepare("UPDATE orders SET is_paid = 3, payment_amount = 0, change_amount = 0, note = ? WHERE id = ?")->execute([$note, $order_id]);
    $pdo->commit();
    $_SESSION['flash_success'] = 'Order dibatalkan/retur. Stok dikembalikan.';
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['flash_error'] = 'Gagal batalkan order: ' . $e->getMessage();
}

header("Location: {$redirect}");
exit;
