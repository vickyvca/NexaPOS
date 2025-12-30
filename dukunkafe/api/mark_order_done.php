<?php

header('Content-Type: application/json');

$pdo = get_pdo($config);

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$order_id = $input['order_id'] ?? null;
$order_no = $input['order_no'] ?? null;

try {
    if (!$order_id && $order_no) {
        $st = $pdo->prepare('SELECT id FROM orders WHERE order_no = ? AND branch_id = ?');
        $st->execute([$order_no]);
        $order_id = $st->fetchColumn();
    }
    if (!$order_id) {
        echo json_encode(['success' => false, 'error' => 'Missing order id']);
        exit;
    }

    // Set all items READY/SERVED to DONE
    $upd = $pdo->prepare("UPDATE order_items SET status = 'DONE' WHERE order_id = ? AND status IN ('READY','SERVED')");
    $upd->execute([$order_id]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

