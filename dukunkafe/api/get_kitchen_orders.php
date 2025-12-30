<?php

header('Content-Type: application/json');

$pdo = get_pdo($config);

$branch_id = get_current_branch_id();
try {
$stmt = $pdo->query("SELECT 
        oi.id, 
        oi.qty, 
        oi.notes, 
        oi.status,
        o.created_at,
        m.name as menu_name, 
        m.print_station,
        o.order_no,
        t.name as table_name
    FROM order_items oi
    JOIN menus m ON oi.menu_id = m.id
    JOIN orders o ON oi.order_id = o.id
    LEFT JOIN tables t ON o.table_id = t.id
    WHERE oi.status IN ('QUEUED', 'IN_PROGRESS') AND o.branch_id = {$branch_id}
    ORDER BY o.created_at ASC");
} catch (Exception $e) {
    $stmt = $pdo->query("SELECT 
        oi.id, oi.qty, oi.notes, oi.status, o.created_at,
        m.name as menu_name, m.print_station, o.order_no, t.name as table_name
        FROM order_items oi
        JOIN menus m ON oi.menu_id = m.id
        JOIN orders o ON oi.order_id = o.id
        LEFT JOIN tables t ON o.table_id = t.id
        WHERE oi.status IN ('QUEUED', 'IN_PROGRESS')
        ORDER BY o.created_at ASC");
}

$items = $stmt->fetchAll();

// Fetch addons for these order items
$ids = array_column($items, 'id');
$addons_map = [];
if (!empty($ids)) {
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $qa = $pdo->prepare("SELECT oia.order_item_id, a.name, oia.price FROM order_item_addons oia JOIN addons a ON a.id = oia.addon_id WHERE oia.order_item_id IN ($ph)");
    $qa->execute($ids);
    foreach ($qa->fetchAll() as $row) {
        $oid = $row['order_item_id'];
        if (!isset($addons_map[$oid])) { $addons_map[$oid] = []; }
        $addons_map[$oid][] = [ 'name' => $row['name'], 'price' => (float)$row['price'] ];
    }
}

foreach ($items as &$it) {
    $it['addons'] = $addons_map[$it['id']] ?? [];
}
unset($it);

echo json_encode($items);
