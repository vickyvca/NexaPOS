<?php

header('Content-Type: application/json');

$pdo = get_pdo($config);

// We need the base status and when it was last updated to calculate duration.
$branch_id = get_current_branch_id();
$sql = "
    SELECT 
        t.id, t.code, t.name, t.area, t.capacity, 
        t.status as base_status,
        t.status_updated_at,
        (SELECT o.status FROM orders o WHERE o.table_id = t.id AND o.status = 'OPEN' AND o.branch_id = {$branch_id} ORDER BY o.created_at DESC LIMIT 1) as last_open_order_status,
        (SELECT GROUP_CONCAT(oi.status) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.table_id = t.id AND o.status = 'OPEN' AND o.branch_id = {$branch_id}) as item_statuses
    FROM tables t
";

try { $stmt = $pdo->query($sql); }
catch (Exception $e) {
    // Fallback without branch filter
    $sql = "
        SELECT t.id, t.code, t.name, t.area, t.capacity,
               t.status as base_status, t.status_updated_at,
               (SELECT o.status FROM orders o WHERE o.table_id = t.id AND o.status = 'OPEN' ORDER BY o.created_at DESC LIMIT 1) as last_open_order_status,
               (SELECT GROUP_CONCAT(oi.status) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.table_id = t.id AND o.status = 'OPEN') as item_statuses
        FROM tables t
    ";
    $stmt = $pdo->query($sql);
}
$tables_data = $stmt->fetchAll();

$result = [];
$now = new DateTime();

foreach ($tables_data as $table) {
    $dynamic_status = $table['base_status']; // Default to base status

    // Dynamically determine status based on open orders
    if ($table['last_open_order_status'] === 'OPEN') {
        $dynamic_status = 'OCCUPIED'; // General occupied status
        if ($table['item_statuses']) {
            $item_statuses = explode(',', $table['item_statuses']);
            
            // If any item is being cooked, the table is 'COOKING'
            if (in_array('IN_PROGRESS', $item_statuses) || in_array('QUEUED', $item_statuses)) {
                $dynamic_status = 'COOKING';
            }

            // If there are no cooking items, check for ready items
            if ($dynamic_status === 'OCCUPIED') { 
                $all_served_or_ready = true;
                $any_ready = false;
                foreach($item_statuses as $is) {
                    if (!in_array($is, ['READY', 'SERVED', 'DONE'])) {
                        $all_served_or_ready = false;
                        break;
                    }
                    if ($is === 'READY') {
                        $any_ready = true;
                    }
                }
                if ($all_served_or_ready && $any_ready) {
                    $dynamic_status = 'READY';
                }
            }
        }
    }

    // Calculate duration
    $duration_minutes = null;
    if ($table['status_updated_at']) {
        $status_time = new DateTime($table['status_updated_at']);
        $interval = $now->diff($status_time);
        $duration_minutes = ($interval->days * 24 * 60) + ($interval->h * 60) + $interval->i;
    }

    $result[] = [
        'id' => $table['id'],
        'name' => $table['name'],
        'capacity' => $table['capacity'],
        'status' => $dynamic_status, // The more descriptive, dynamic status
        'base_status' => $table['base_status'], // The underlying status from the DB (AVAILABLE, OCCUPIED, etc)
        'status_duration_minutes' => $duration_minutes,
    ];
}

echo json_encode($result);
