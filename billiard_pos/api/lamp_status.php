<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/lamp_control.php';
check_login();
header('Content-Type: application/json');

$table_id = (int)($_GET['table_id'] ?? 0);
if (!$table_id) {
    echo json_encode(['status' => 'error', 'message' => 'table_id wajib']);
    exit;
}
$stmt = $pdo->prepare("SELECT controller_ip FROM billiard_tables WHERE id = ?");
$stmt->execute([$table_id]);
$tbl = $stmt->fetch();
if (!$tbl || empty($tbl['controller_ip'])) {
    echo json_encode(['status' => 'error', 'message' => 'IP belum di-set']);
    exit;
}

$ip = $tbl['controller_ip'];
$url = "http://{$ip}/status";
$ctx = stream_context_create(['http' => ['timeout' => 2]]);
$resp = @file_get_contents($url, false, $ctx);
if ($resp === false) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal hubungi '.$url]);
    exit;
}

echo json_encode(['status' => 'ok', 'message' => $resp]);
