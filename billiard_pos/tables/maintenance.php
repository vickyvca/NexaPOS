<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/lamp_control.php';
check_login();
header('Content-Type: application/json');

$table_id = (int)($_POST['table_id'] ?? 0);
$action = $_POST['action'] ?? 'on';
$password = $_POST['password'] ?? '';

global $maintenance_password, $maintenance_duration_minutes;

if ($password !== $maintenance_password) {
    echo json_encode(['status' => 'error', 'message' => 'Password salah']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM billiard_tables WHERE id = ?");
$stmt->execute([$table_id]);
$table = $stmt->fetch();
if (!$table) {
    echo json_encode(['status' => 'error', 'message' => 'Meja tidak ditemukan']);
    exit;
}

if ($action === 'on') {
    $role = $_SESSION['user']['role'] ?? 'kasir';
    $duration = ($role === 'admin')
        ? max(1, (int)($_POST['duration'] ?? $maintenance_duration_minutes))
        : 5; // kasir fixed 5 menit
    // insert log
    $end = date('Y-m-d H:i:s', strtotime("+{$duration} minutes"));
    $logStmt = $pdo->prepare("INSERT INTO maintenance_logs (table_id, operator_id, start_time, end_time, duration_minutes, note) VALUES (?,?,?,?,?,?)");
    $logStmt->execute([$table_id, $_SESSION['user']['id'], date('Y-m-d H:i:s'), $end, $duration, 'Maintenance/cleaning']);
    $_SESSION['maintenance_log'][$table_id] = $pdo->lastInsertId();
    $pdo->prepare("UPDATE billiard_tables SET status='maintenance' WHERE id=?")->execute([$table_id]);
    if ($table['controller_ip'] && $table['relay_channel']) {
        call_lamp($table['controller_ip'], $table['relay_channel'], 'on');
    }
    echo json_encode(['status' => 'ok', 'message' => 'Maintenance ON', 'duration' => $duration, 'end_time' => $end]);
} else {
    // update log
    $logId = $_SESSION['maintenance_log'][$table_id] ?? null;
    if (!$logId) {
        $logId = $pdo->prepare("SELECT id FROM maintenance_logs WHERE table_id = ? AND end_time >= NOW() ORDER BY id DESC LIMIT 1");
        $logId->execute([$table_id]);
        $logId = $logId->fetchColumn();
    }
    if ($logId) {
        $pdo->prepare("UPDATE maintenance_logs SET end_time = NOW() WHERE id = ?")->execute([$logId]);
        unset($_SESSION['maintenance_log'][$table_id]);
    }
    $pdo->prepare("UPDATE billiard_tables SET status='idle' WHERE id=?")->execute([$table_id]);
    if ($table['controller_ip'] && $table['relay_channel']) {
        call_lamp($table['controller_ip'], $table['relay_channel'], 'off');
    }
    echo json_encode(['status' => 'ok', 'message' => 'Maintenance OFF']);
}
?>
