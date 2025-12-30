<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/lamp_control.php';
check_login();
header('Content-Type: application/json');

$table_id = (int)($_POST['table_id'] ?? $_GET['table_id'] ?? 0);
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$pass = $_POST['pass'] ?? $_GET['pass'] ?? '';

if (!$table_id || !in_array($action, ['on','off'], true)) {
    echo json_encode(['status' => 'error', 'message' => 'table_id dan action=on/off wajib']);
    exit;
}

$settings = load_company_settings();
$needPass = $settings['maintenance_password'] ?? '';
// jika ada password, wajib cocok untuk semua user
if ($needPass !== '') {
    if ($pass !== $needPass) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Password salah / wajib diisi']);
        exit;
    }
} else {
    // jika tidak ada password, hanya admin yang boleh kontrol lampu per meja
    if (empty($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Hanya admin yang boleh kontrol lampu'] );
        exit;
    }
}

$stmt = $pdo->prepare("SELECT controller_ip, relay_channel, status FROM billiard_tables WHERE id = ?");
$stmt->execute([$table_id]);
$tbl = $stmt->fetch();
if (!$tbl || !$tbl['controller_ip'] || !$tbl['relay_channel']) {
    echo json_encode(['status' => 'error', 'message' => 'IP/Channel belum di-set']);
    exit;
}
if ($tbl['status'] === 'running') {
    echo json_encode(['status' => 'error', 'message' => 'Lewati: meja sedang berjalan']);
    exit;
}

try {
    $resp = call_lamp($tbl['controller_ip'], $tbl['relay_channel'], $action);
    echo json_encode(['status' => 'ok', 'message' => 'Lampu ' . $action, 'resp' => $resp]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
