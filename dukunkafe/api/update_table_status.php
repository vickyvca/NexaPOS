<?php
// api/update_table_status.php

header('Content-Type: application/json');

// require_auth(); // TODO: Add authentication if needed

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$table_id = $data['id'] ?? null;
$status = $data['status'] ?? null;

if (!$table_id || !$status) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing table ID or status']);
    exit();
}

$allowed_statuses = ['AVAILABLE', 'CLEANING']; 
if (!in_array($status, $allowed_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid or disallowed status']);
    exit();
}

$pdo = get_pdo($config);

try {
    $stmt = $pdo->prepare("
        UPDATE tables 
        SET status = ?, status_updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$status, $table_id]);

    if ($stmt->rowCount() > 0) {
        // If setting to AVAILABLE, also close any open orders linked to this table.
        if ($status === 'AVAILABLE') {
            $order_check_stmt = $pdo->prepare("UPDATE orders SET status = 'CLOSED', closed_at = NOW() WHERE table_id = ? AND status = 'OPEN' AND branch_id = ?");
            $order_check_stmt->execute([$table_id, get_current_branch_id()]);
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status or table not found.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
