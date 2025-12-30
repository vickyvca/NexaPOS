<?php

require_auth(['admin','kasir','waiter']);

$pdo = get_pdo($config);

$order_id = $_GET['id'] ?? null;
$order_no = $_GET['order_no'] ?? null;

if (!$order_id && $order_no) {
    $st = $pdo->prepare('SELECT id FROM orders WHERE order_no = ?');
    $st->execute([$order_no]);
    $order_id = $st->fetchColumn();
}

if (!$order_id) {
    die('Order id is required');
}

$stmt = $pdo->prepare("SELECT o.*, t.name AS table_name FROM orders o LEFT JOIN tables t ON o.table_id = t.id WHERE o.id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch();
if (!$order) die('Order not found');

$items_stmt = $pdo->prepare("SELECT oi.id, oi.qty, oi.notes, m.name as menu_name FROM order_items oi JOIN menus m ON oi.menu_id = m.id WHERE oi.order_id = ?");
$items_stmt->execute([$order_id]);
$items = $items_stmt->fetchAll();

// Addons per item
$ids = array_column($items, 'id');
$addons_map = [];
if (!empty($ids)) {
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $qa = $pdo->prepare("SELECT oia.order_item_id, a.name FROM order_item_addons oia JOIN addons a ON a.id = oia.addon_id WHERE oia.order_item_id IN ($ph)");
    $qa->execute($ids);
    foreach ($qa->fetchAll() as $row) {
        $addons_map[$row['order_item_id']][] = $row['name'];
    }
}

foreach ($items as &$it) {
    $it['addons'] = $addons_map[$it['id']] ?? [];
}
unset($it);

require __DIR__ . '/order-slip.view.php';

