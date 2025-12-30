<?php

header('Content-Type: application/json');

$pdo = get_pdo($config);

// Step 1: Find order IDs that are active and have items that are either ready or have been recently served.
$branch_id = get_current_branch_id();
$order_ids_sql = "
    SELECT DISTINCT oi.order_id 
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status IN ('OPEN','CLOSED') 
      AND o.created_at > NOW() - INTERVAL 24 HOUR
      AND oi.status IN ('READY', 'SERVED')
      AND o.branch_id = {$branch_id}
";
$stmt = $pdo->query($order_ids_sql);
$order_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($order_ids)) {
    echo json_encode([]);
    exit;
}

// Step 2: Fetch all items for those orders, along with order details.
$placeholders = implode(',', array_fill(0, count($order_ids), '?'));

$sql = "
    SELECT
        o.id as order_id,
        o.order_no,
        o.customer_name,
        o.created_at,
        t.name as table_name,
        oi.id as item_id,
        m.name as item_name,
        oi.qty,
        oi.status,
        oi.ready_at
    FROM orders o
    LEFT JOIN tables t ON o.table_id = t.id
    JOIN order_items oi ON o.id = oi.order_id
    JOIN menus m ON oi.menu_id = m.id
    WHERE o.id IN ($placeholders) AND o.branch_id = {$branch_id}
ORDER BY o.id, oi.id
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($order_ids);
} catch (Exception $e) {
    $sql = str_replace(' AND o.branch_id = {$branch_id}', '', $sql);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($order_ids);
}
$all_items = $stmt->fetchAll();

// Step 3: Group items by order in PHP.
$orders = [];
foreach ($all_items as $item) {
    $order_id = $item['order_id'];
    if (!isset($orders[$order_id])) {
        $orders[$order_id] = [
            'order_id' => $order_id,
            'order_no' => $item['order_no'],
            'customer_name' => $item['customer_name'],
            'table_name' => $item['table_name'],
            'created_at' => $item['created_at'],
            'items' => []
        ];
    }
    $orders[$order_id]['items'][] = [
        'id' => $item['item_id'],
        'name' => $item['item_name'],
        'qty' => $item['qty'],
        'status' => $item['status'],
        'ready_at' => $item['ready_at']
    ];
}

// Find the earliest ready time for each order
foreach ($orders as $order_id => &$order) {
    $first_ready_time = null;
    foreach ($order['items'] as $item) {
        if ($item['ready_at'] !== null) {
            $item_time = strtotime($item['ready_at']);
            if ($first_ready_time === null || $item_time < $first_ready_time) {
                $first_ready_time = $item_time;
            }
        }
    }
    
    if ($first_ready_time !== null) {
        $order['ready_at'] = date('Y-m-d H:i:s', $first_ready_time);
    } else {
        // Fallback if no item has a ready_at time (e.g., only SERVED items are fetched and they were marked ready before this logic was added)
        // In this case, we can't know the ready time, so we'll fall back to the order creation time.
        $order['ready_at'] = $order['created_at'];
    }
}
unset($order);


// Step 4: Filter out orders where all items are fully served or done.
$final_orders = [];
foreach ($orders as $order) {
    $all_items_done = true;
    $has_at_least_one_ready = false;
    foreach ($order['items'] as $item) {
        if (!in_array($item['status'], ['SERVED', 'DONE'])) {
            $all_items_done = false;
        }
        if ($item['status'] === 'READY') {
            $has_at_least_one_ready = true;
        }
    }

    if (!$all_items_done && ($has_at_least_one_ready || array_filter($order['items'], fn($i) => $i['status'] === 'SERVED'))) {
        $final_orders[] = $order;
    }
}


echo json_encode(array_values($final_orders));
