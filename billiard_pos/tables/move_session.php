<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/lamp_control.php';
check_login();
header('Content-Type: application/json');

$from_table = (int)($_POST['from_table_id'] ?? 0);
$to_table   = (int)($_POST['to_table_id'] ?? 0);

if (!$from_table || !$to_table) {
    echo json_encode(['status' => 'error', 'message' => 'Parameter dari/ke meja wajib diisi']);
    exit;
}
if ($from_table === $to_table) {
    echo json_encode(['status' => 'error', 'message' => 'Meja tujuan harus berbeda']);
    exit;
}

// session berjalan di meja asal
$sessStmt = $pdo->prepare("SELECT s.*, t.controller_ip, t.relay_channel FROM sessions s JOIN billiard_tables t ON s.table_id = t.id WHERE s.table_id = ? AND s.status = 'running' LIMIT 1");
$sessStmt->execute([$from_table]);
$session = $sessStmt->fetch();
if (!$session) {
    echo json_encode(['status' => 'error', 'message' => 'Tidak ada sesi berjalan di meja asal']);
    exit;
}

// meja tujuan
$tableToStmt = $pdo->prepare("SELECT * FROM billiard_tables WHERE id = ?");
$tableToStmt->execute([$to_table]);
$toTable = $tableToStmt->fetch();
if (!$toTable) {
    echo json_encode(['status' => 'error', 'message' => 'Meja tujuan tidak ditemukan']);
    exit;
}
if ($toTable['status'] !== 'idle') {
    echo json_encode(['status' => 'error', 'message' => 'Meja tujuan tidak idle']);
    exit;
}

// tarif asal & tujuan
$oldTariffStmt = $pdo->prepare("SELECT * FROM tariffs WHERE id = ?");
$oldTariffStmt->execute([$session['tariff_id']]);
$oldTariff = $oldTariffStmt->fetch();
if (!$oldTariff) {
    echo json_encode(['status' => 'error', 'message' => 'Tarif asal tidak ditemukan']);
    exit;
}
$newTariff = get_applicable_tariff($pdo, $to_table);

// hitung parsial meja asal TANPA min_minutes (hanya waktu berjalan aktual)
$elapsedMinutes = (int)floor((time() - strtotime($session['start_time'])) / 60);
if ($elapsedMinutes < 0) { $elapsedMinutes = 0; }
$ratePerMinute = ((int)$oldTariff['rate_per_hour']) / 60;
if (!empty($oldTariff['block_minutes']) && !empty($oldTariff['block_price'])) {
    $bm = (int)$oldTariff['block_minutes'];
    $bp = (int)$oldTariff['block_price'];
    $ratePerMinute = $bm > 0 ? ($bp / $bm) : $ratePerMinute;
}
$carryMinutes = $elapsedMinutes;
$carryAmount  = (int)ceil($elapsedMinutes * $ratePerMinute);

try {
    $pdo->beginTransaction();
    // simpan akumulasi lama, reset start_time untuk meja baru
    $updSess = $pdo->prepare("UPDATE sessions SET table_id = ?, tariff_id = ?, start_time = NOW(), total_minutes = ?, total_amount = ? WHERE id = ?");
    $updSess->execute([$to_table, $newTariff['id'], $carryMinutes, $carryAmount, $session['id']]);

    $pdo->prepare("UPDATE billiard_tables SET status = 'idle' WHERE id = ?")->execute([$from_table]);
    $pdo->prepare("UPDATE billiard_tables SET status = 'running' WHERE id = ?")->execute([$to_table]);

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $e->getMessage()]);
    exit;
}

// kontrol lampu
if ($session['controller_ip'] && $session['relay_channel']) {
    call_lamp($session['controller_ip'], $session['relay_channel'], 'off');
}
if ($toTable['controller_ip'] && $toTable['relay_channel']) {
    call_lamp($toTable['controller_ip'], $toTable['relay_channel'], 'on');
}

// Notifikasi pindah meja (via Fonnte jika di-set)
$settings = load_company_settings();
if (!empty($settings['fonnte_target'])) {
    $operator = $_SESSION['user']['username'] ?? 'kasir';
    $msg = "Pindah meja (hemat notif)\n"
         . "Dari: {$from_table} (" . ($session['customer_name'] ?? '-') . ")\n"
         . "Ke  : {$to_table} (" . ($toTable['name'] ?? '-') . ")\n"
         . "Kasir: {$operator}\n"
         . "Akumulasi lama: " . human_duration($carryMinutes) . " (" . format_rupiah($carryAmount) . ")\n"
         . "Tarif baru: " . ($newTariff['name'] ?? '-');
    send_fonnte_notification($settings['fonnte_target'], $msg);
}

echo json_encode([
    'status' => 'ok',
    'session_id' => $session['id'],
    'from_table' => $from_table,
    'to_table' => $to_table,
    'carried_minutes' => $carryMinutes,
    'carried_amount' => $carryAmount,
    'new_tariff' => $newTariff['name'] ?? ''
]);
