<?php

require_auth(['admin', 'kasir']);

$title = 'Receipt';
$pdo = get_pdo($config);

$order_id = $_GET['id'] ?? null;

if (!$order_id) {
    die("Order ID is required.");
}

// Fetch order details
$stmt = $pdo->prepare("
    SELECT 
        o.*, 
        t.name as table_name
    FROM orders o
    LEFT JOIN tables t ON o.table_id = t.id
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    die("Order not found.");
}

// Fetch order items
$items_stmt = $pdo->prepare("
    SELECT oi.qty, oi.price, m.name as menu_name
    FROM order_items oi
    JOIN menus m ON oi.menu_id = m.id
    WHERE oi.order_id = ?
");
$items_stmt->execute([$order_id]);
$items = $items_stmt->fetchAll();

// For cashier name, use the currently logged-in user as a fallback.
$order['cashier_name'] = $_SESSION['user']['name'];

// Load cafe settings for header (name, address, logo)
$settings = load_settings($pdo);

// This is a standalone page
require __DIR__ . '/receipt.view.php';