<?php
require_once __DIR__ . '/../includes/functions.php';
check_login();
header('Content-Type: application/json');

$table_id = (int)($_GET['table_id'] ?? 0);
if (!$table_id) {
    echo json_encode(['status' => 'error', 'message' => 'table_id wajib']);
    exit;
}

// Ambil sesi + nama meja
$stmt = $pdo->prepare("SELECT s.id, s.start_time, s.customer_name, s.customer_phone, s.member_id, s.package_id, s.table_id, t.name AS table_name, TIMESTAMPDIFF(MINUTE, s.start_time, NOW()) AS minutes FROM sessions s JOIN billiard_tables t ON t.id = s.table_id WHERE s.table_id = ? AND s.status = 'running' LIMIT 1");
$stmt->execute([$table_id]);
$session = $stmt->fetch();

// Fonnte notif helper untuk reminder sisa 5 menit
function maybe_send_last5_notification(PDO $pdo, $session) {
    // target prioritas: no. WA user (racker) yang login; fallback ke company default
    $target = $_SESSION['user']['phone'] ?? '';
    if (!$target) {
        $settings = load_company_settings();
        $target = $settings['fonnte_target'] ?? '';
    }
    if (!$target) return; // tidak kirim jika tidak ada target

    $storageDir = __DIR__ . '/../storage';
    if (!is_dir($storageDir)) @mkdir($storageDir, 0777, true);
    $flagFile = $storageDir . '/warn_session_' . $session['id'] . '.json';
    $flags = ['package_sent' => false, 'last_hour_warn' => null];
    if (file_exists($flagFile)) {
        $json = json_decode(file_get_contents($flagFile), true);
        if (is_array($json)) $flags = array_merge($flags, $json);
    }

    $minutes = (int)$session['minutes'];
    $package = get_package($pdo, $session['package_id']);
    $nowHourSlot = (int)floor($minutes / 60) + 1; // jam ke berapa (1-based)
    $remToHour = 60 - ($minutes % 60);

    // Pesan dasar
    $baseInfo = "Meja: ".$session['table_name']."\nCustomer: ".($session['customer_name'] ?: '-')."\nDurasi: ".$minutes." mnt";

    if ($package) {
        // Paket: kirim sekali saat sisa <= 5 menit sebelum durasi paket
        $remPkg = max(0, (int)$package['duration_minutes'] - $minutes);
        if ($remPkg <= 5 && !$flags['package_sent']) {
            $msg = "Paket hampir habis (≤5 mnt)\n".$baseInfo."\nPaket: ".$package['name']." (".$package['duration_minutes']." mnt)\nLanjut tanpa paket atau close?";
            send_fonnte_notification($target, $msg);
            $flags['package_sent'] = true;
        }
    } else {
        // Non paket: kirim tiap mau habis jam (sisa <=5 mnt) sekali per jam
        if ($remToHour <= 5) {
            if ($flags['last_hour_warn'] !== $nowHourSlot) {
                $msg = "Durasi hampir jam ke-".$nowHourSlot." (≤5 mnt)\n".$baseInfo."\nTawarkan lanjut atau tutup?";
                send_fonnte_notification($target, $msg);
                $flags['last_hour_warn'] = $nowHourSlot;
            }
        }
    }

    file_put_contents($flagFile, json_encode($flags));
}

if ($session) {
    // Kirim reminder jika mendekati batas (paket atau setiap jam) ke nomor WA default
    maybe_send_last5_notification($pdo, $session);
    echo json_encode(['status' => 'running', 'session' => $session]);
} else {
    echo json_encode(['status' => 'idle']);
}
?>
