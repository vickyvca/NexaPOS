<?php

require_auth(['admin', 'kasir', 'manager']);

$title = 'Transaksi';
$pdo = get_pdo($config);

// Filters
$q = trim($_GET['q'] ?? '');
$date_from = $_GET['from'] ?? '';
$date_to = $_GET['to'] ?? '';
$status = $_GET['status'] ?? 'CLOSED'; // default to CLOSED transactions

$params = [];
$where = [];

if ($status !== 'ALL') {
    $where[] = 'o.status = ?';
    $params[] = $status;
}

if ($q !== '') {
    $where[] = '(o.order_no LIKE ? OR o.customer_name LIKE ? OR t.name LIKE ?)';
    $like = "%$q%";
    $params[] = $like; $params[] = $like; $params[] = $like;
}

if ($date_from !== '') {
    $where[] = 'DATE(o.created_at) >= ?';
    $params[] = $date_from;
}
if ($date_to !== '') {
    $where[] = 'DATE(o.created_at) <= ?';
    $params[] = $date_to;
}

$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "
    SELECT o.*, t.name AS table_name
    FROM orders o
    LEFT JOIN tables t ON o.table_id = t.id
    $where_sql
    ORDER BY o.created_at DESC
    LIMIT 200
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

view('transactions', [
    'title' => $title,
    'orders' => $orders,
    'filters' => [
        'q' => $q,
        'from' => $date_from,
        'to' => $date_to,
        'status' => $status,
    ],
]);

