<?php
require_once __DIR__ . '/api_common.php';
require_roles(['pegawai']);

$nik = $_SESSION['nik'] ?? '';
if (!$nik) {
    json_error('NIK tidak ditemukan.', 401);
}

$shift_times = [
    'S1' => ['start' => '08:30', 'end' => '18:00', 'name' => 'Shift 1 (Pagi)'],
    'S2' => ['start' => '12:00', 'end' => '20:30', 'name' => 'Shift 2 (Siang)'],
];

$today_date = date('Y-m-d');
$response = [
    'status' => 'success',
    'masuk' => null,
    'pulang' => null,
    'overtime' => false,
];

try {
    $sql = "SELECT SHIFT_MASUK, SHIFT_PULANG, OVERTIME_BONUS_FLAG, SHIFT_JADWAL
            FROM T_ABSENSI
            WHERE KODESL = :nik AND TGL = :tgl";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':nik' => $nik, ':tgl' => $today_date]);
    $result_absen = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result_absen) {
        $response['masuk'] = $result_absen['SHIFT_MASUK'] ? date('H:i', strtotime($result_absen['SHIFT_MASUK'])) : null;
        $response['pulang'] = $result_absen['SHIFT_PULANG'] ? date('H:i', strtotime($result_absen['SHIFT_PULANG'])) : null;
        $response['overtime'] = (bool)$result_absen['OVERTIME_BONUS_FLAG'];

        $shift_code = $result_absen['SHIFT_JADWAL'] ?: 'S1';
        $end_time_cfg = $shift_times[$shift_code]['end'] ?? '18:00';
        $now = date('H:i');
        if ($response['masuk'] && !$response['pulang'] && $now >= $end_time_cfg) {
            $stmt2 = $conn->prepare("UPDATE T_ABSENSI SET SHIFT_PULANG = :end_time WHERE KODESL = :nik AND TGL = :tgl");
            $stmt2->execute([':end_time' => $end_time_cfg . ':00', ':nik' => $nik, ':tgl' => $today_date]);
            $response['pulang'] = $end_time_cfg;
        }
    }
} catch (PDOException $e) {
    json_error('DB Query Error (T_ABSENSI): ' . $e->getMessage(), 500);
}

json_ok(['data' => $response]);
