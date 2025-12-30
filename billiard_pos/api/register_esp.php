<?php
// Endpoint untuk update otomatis IP ESP ke master meja.
// Panggil contoh: /billiard_pos/api/register_esp.php?table_id=1&ip=192.168.1.50&channel=1
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

$table_id = (int)($_GET['table_id'] ?? 0);
$ip = trim($_GET['ip'] ?? '');
$channel = isset($_GET['channel']) ? (int)$_GET['channel'] : null;

if (!$table_id || !$ip) {
    echo json_encode(['status' => 'error', 'message' => 'table_id dan ip wajib']);
    exit;
}

try {
    $sql = "UPDATE billiard_tables SET controller_ip = ?";
    $params = [$ip];
    if ($channel !== null) {
        $sql .= ", relay_channel = ?";
        $params[] = $channel;
    }
    $sql .= " WHERE id = ?";
    $params[] = $table_id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['status' => 'ok', 'table_id' => $table_id, 'ip' => $ip, 'channel' => $channel]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
