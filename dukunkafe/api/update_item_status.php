
<?php

header('Content-Type: application/json');


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$item_id = $data['id'] ?? null;
$status = $data['status'] ?? null;

if (!$item_id || !$status) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing item ID or status']);
    exit();
}

$allowed_statuses = ['IN_PROGRESS', 'READY', 'SERVED', 'DONE'];
if (!in_array($status, $allowed_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

$pdo = get_pdo($config);

$sql = "UPDATE order_items SET status = ?";
$params = [$status];

if ($status === 'IN_PROGRESS') {
    $sql .= ", started_at = NOW() WHERE id = ? AND status = 'QUEUED'";
} elseif ($status === 'READY') {
    $sql .= ", ready_at = NOW() WHERE id = ? AND status = 'IN_PROGRESS'";
} elseif ($status === 'SERVED') {
    $sql .= ", served_at = NOW() WHERE id = ? AND status = 'READY'";
} elseif ($status === 'DONE') {
    $sql .= " WHERE id = ? AND status IN ('READY','SERVED')";
}

$params[] = $item_id;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update status. Item may not be in the correct preceding state.']);
}
