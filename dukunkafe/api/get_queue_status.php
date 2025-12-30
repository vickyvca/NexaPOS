
<?php

header('Content-Type: application/json');


$pdo = get_pdo($config);
$branch_id = get_current_branch_id();

// Fetch orders that are not closed or canceled and were created in the last 24 hours.
// Fetch orders and items with statuses in the last 24 hours (or OPEN)
try { $stmt = $pdo->query("SELECT 
        o.id as order_id,
        o.order_no,
        o.status as order_status,
        o.created_at,
        t.name as table_name,
        oi.id as order_item_id,
        oi.qty,
        oi.status as item_status,
        m.name as menu_name
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN menus m ON oi.menu_id = m.id
    LEFT JOIN tables t ON o.table_id = t.id
    WHERE o.status IN ('OPEN','CLOSED') AND o.created_at > NOW() - INTERVAL 24 HOUR AND o.branch_id = {$branch_id}
    ORDER BY o.created_at ASC, o.id ASC"); }
catch (Exception $e) {
    $stmt = $pdo->query("SELECT 
        o.id as order_id, o.order_no, o.status as order_status, o.created_at,
        t.name as table_name, oi.id as order_item_id, oi.qty, oi.status as item_status, m.name as menu_name
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN menus m ON oi.menu_id = m.id
        LEFT JOIN tables t ON o.table_id = t.id
        WHERE o.status IN ('OPEN','CLOSED') AND o.created_at > NOW() - INTERVAL 24 HOUR
        ORDER BY o.created_at ASC, o.id ASC");
}

$rows = $stmt->fetchAll();

$orders = [];
foreach ($rows as $r) {
    $oid = $r['order_id'];
    if (!isset($orders[$oid])) {
        $orders[$oid] = [
            'order_id' => $oid,
            'order_no' => $r['order_no'],
            'table_name' => $r['table_name'],
            'created_at' => $r['created_at'],
            'items' => []
        ];
    }
    $orders[$oid]['items'][] = [
        'id' => $r['order_item_id'],
        'name' => $r['menu_name'],
        'qty' => (int)$r['qty'],
        'status' => $r['item_status']
    ];
}

$preparing = [];
$ready = [];
$served = [];

foreach ($orders as $o) {
    $statuses = array_column($o['items'], 'status');
    $total = count($statuses);
    $all_ready = $total > 0 && count(array_filter($statuses, fn($s) => $s === 'READY')) === $total;
    $has_work = count(array_filter($statuses, fn($s) => in_array($s, ['QUEUED','IN_PROGRESS']))) > 0;
    $all_served = $total > 0 && count(array_filter($statuses, fn($s) => in_array($s, ['SERVED','DONE']))) === $total;

    if ($has_work) {
        $preparing[] = $o;
    } elseif ($all_ready) {
        $ready[] = $o;
    } elseif ($all_served) {
        $served[] = $o;
    }
}

echo json_encode([
    'preparing' => array_values($preparing),
    'ready' => array_values($ready),
    'served' => array_values($served)
]);
