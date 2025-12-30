<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/lamp_control.php';
check_login();

header('Content-Type: application/json');
$table_id = (int)($_POST['table_id'] ?? 0);
$customer_name = trim($_POST['customer_name'] ?? '');
$customer_phone = trim($_POST['customer_phone'] ?? '');
$mode = $_POST['mode'] ?? 'umum';
$member_id = (int)($_POST['member_id'] ?? 0);
$package_id = (int)($_POST['package_id'] ?? 0);
$package_id = (int)($_POST['package_id'] ?? 0);

if (!$table_id) {
    echo json_encode(['status' => 'error', 'message' => 'Table ID tidak valid']);
    exit;
}
$customer_phone = preg_replace('/[^0-9+]/', '', $customer_phone);
$member = null;
if ($member_id) {
    $mode = 'member';
}
if ($mode !== 'member') {
    $member_id = 0;
}
if ($member_id) {
    $mst = $pdo->prepare("SELECT * FROM members WHERE id = ? AND is_active = 1");
    $mst->execute([$member_id]);
    $member = $mst->fetch();
    if (!$member) {
        echo json_encode(['status' => 'error', 'message' => 'Member tidak ditemukan/aktif']);
        exit;
    }
    $customer_name = $member['name'];
    $customer_phone = $member['phone'];
}
if (!$member) {
    if ($customer_name === '') {
        echo json_encode(['status' => 'error', 'message' => 'Nama customer wajib diisi']);
        exit;
    }
    if ($customer_phone === '') {
        echo json_encode(['status' => 'error', 'message' => 'Nomor HP wajib diisi']);
        exit;
    }
}

$stmt = $pdo->prepare("SELECT * FROM billiard_tables WHERE id = ?");
$stmt->execute([$table_id]);
$table = $stmt->fetch();
if (!$table) {
    echo json_encode(['status' => 'error', 'message' => 'Meja tidak ditemukan']);
    exit;
}
if ($table['status'] !== 'idle') {
    echo json_encode(['status' => 'error', 'message' => 'Meja tidak dalam keadaan idle']);
    exit;
}

$tariff = get_applicable_tariff($pdo, $table_id);
if (!$tariff) {
    echo json_encode(['status' => 'error', 'message' => 'Tarif belum diset']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO sessions (table_id, tariff_id, operator_id, member_id, package_id, customer_name, customer_phone, start_time, status) VALUES (?,?,?,?,?,?,?,NOW(),'running')");
try {
    $stmt->execute([$table_id, $tariff['id'], $_SESSION['user']['id'], $member['id'] ?? null, $package_id ?: null, $customer_name, $customer_phone]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()]);
    exit;
}
$session_id = $pdo->lastInsertId();

$pdo->prepare("UPDATE billiard_tables SET status = 'running' WHERE id = ?")->execute([$table_id]);

if ($table['controller_ip'] && $table['relay_channel']) {
    call_lamp($table['controller_ip'], $table['relay_channel'], 'on');
}

// set selected member for POS/checkout continuity
$_SESSION['selected_member'][$table_id] = $member['id'] ?? null;

// Tidak kirim notif saat start (irit kuota); notif hanya saat pindah/stop/rekap

echo json_encode(['status' => 'ok', 'session_id' => $session_id, 'table_id' => $table_id]);
?>
