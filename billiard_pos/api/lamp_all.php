<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/lamp_control.php';
check_login();
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$pass = $_POST['pass'] ?? $_GET['pass'] ?? '';
if (!in_array($action, ['on_all','off_all'], true)) {
    echo json_encode(['status' => 'error', 'message' => 'action harus on_all/off_all']);
    exit;
}
$settings = load_company_settings();
$needPass = $settings['maintenance_password'] ?? '';
if ($needPass !== '') {
    if ($pass !== $needPass) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Password salah / wajib diisi']);
        exit;
    }
}

$duration = (int)($_POST['duration'] ?? $_GET['duration'] ?? 10); // menit
$tablesAll = $pdo->query("SELECT id, controller_ip, relay_channel, status FROM billiard_tables WHERE controller_ip IS NOT NULL AND relay_channel IS NOT NULL")->fetchAll();
$countOk = 0;
foreach ($tablesAll as $t) {
    if ($t['status'] === 'running') {
        continue; // skip meja yang sedang berjalan
    }
    try {
        call_lamp($t['controller_ip'], $t['relay_channel'], $action === 'on_all' ? 'on' : 'off');
        $countOk++;
    } catch (Exception $e) {
        // abaikan per meja
    }
}
$msg = ($action === 'on_all') ? "Semua lampu dinyalakan ($countOk meja) durasi {$duration} menit." : "Semua lampu dimatikan ($countOk meja).";

// notif WA default jika ada
$settings = load_company_settings();
if (!empty($settings['fonnte_target']) && !empty($settings['fonnte_token'])) {
    send_fonnte_notification($settings['fonnte_target'], $msg);
}

echo json_encode(['status' => 'ok', 'message' => $msg, 'count' => $countOk, 'time' => date('H:i:s')]);
