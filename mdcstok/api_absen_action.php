<?php
require_once __DIR__ . '/api_common.php';
require_once __DIR__ . '/payroll/payroll_lib.php';
require_roles(['pegawai']);

$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: [];
}

$nik = $_SESSION['nik'] ?? '';
$action = $input['action'] ?? null;
$shift_code = $input['shift'] ?? null;
$user_lat = $input['user_lat'] ?? '0';
$user_lon = $input['user_lon'] ?? '0';

if (!$nik) {
    json_error('NIK tidak ditemukan.', 401);
}

$today = date('Y-m-d');
$now_time = date('H:i:s');

$shift_times = [
    'S1' => ['start' => '08:30:00', 'end' => '18:00:00', 'name' => 'Shift 1 (Pagi)'],
    'S2' => ['start' => '12:00:00', 'end' => '20:30:00', 'name' => 'Shift 2 (Siang)'],
];

if (!$shift_code || !isset($shift_times[$shift_code])) {
    json_error('Kode shift tidak valid.', 400);
}

try {
    if ($action === 'lembur') {
        $stmt_check = $conn->prepare("SELECT SHIFT_MASUK, SHIFT_PULANG, OVERTIME_BONUS_FLAG, SHIFT_JADWAL, OVERTIME_NOTES FROM T_ABSENSI WHERE KODESL = :nik AND TGL = :today");
        $stmt_check->execute([':nik' => $nik, ':today' => $today]);
        $existing = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if (!$existing || empty($existing['SHIFT_MASUK'])) {
            json_error('Tidak dapat memulai lembur tanpa absen masuk.', 400);
        }

        $new_note = trim(($existing['OVERTIME_NOTES'] ?? ''));
        if ($new_note !== '') {
            $new_note .= ' | ';
        }
        $new_note .= 'OT Manual Start';

        $sql = "UPDATE T_ABSENSI SET 
                    OVERTIME_BONUS_FLAG = 1,
                    OVERTIME_NOTES = :note,
                    SHIFT_PULANG = NULL
                WHERE KODESL = :nik AND TGL = :today";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':note' => $new_note, ':nik' => $nik, ':today' => $today]);

        json_ok(['message' => 'Lembur dimulai. Jangan lupa absen pulang saat selesai.']);
    }

    if ($action === 'masuk') {
        $stmt_check = $conn->prepare("SELECT SHIFT_MASUK FROM T_ABSENSI WHERE KODESL = :nik AND TGL = :today");
        $stmt_check->execute([':nik' => $nik, ':today' => $today]);
        $existing = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($existing && $existing['SHIFT_MASUK'] !== null) {
            json_error('Anda sudah Absen Masuk hari ini.', 400);
        }

        $overtime_flag = 0;
        $overtime_note = null;
        if ($shift_code === 'S2' && strtotime($now_time) < strtotime('10:00:00')) {
            $overtime_flag = 1;
            $overtime_note = 'OT Masuk Pagi (S2)';
        }

        if ($existing) {
            $sql = "UPDATE T_ABSENSI SET 
                        SHIFT_JADWAL = :shift_code, SHIFT_MASUK = :now_time, STATUS_HARI = 'HADIR', 
                        OVERTIME_BONUS_FLAG = ISNULL(OVERTIME_BONUS_FLAG, 0) | :ot_flag,
                        OVERTIME_NOTES = ISNULL(OVERTIME_NOTES, '') + IIF(ISNULL(OVERTIME_BONUS_FLAG, 0)=1, ' | ' + :ot_note, ''),
                        LAST_LATITUDE = :lat, LAST_LONGITUDE = :lon
                    WHERE KODESL = :nik AND TGL = :today";
        } else {
            $sql = "INSERT INTO T_ABSENSI (KODESL, TGL, SHIFT_JADWAL, SHIFT_MASUK, STATUS_HARI, OVERTIME_BONUS_FLAG, OVERTIME_NOTES, LAST_LATITUDE, LAST_LONGITUDE)
                    VALUES (:nik, :today, :shift_code, :now_time, 'HADIR', :ot_flag, :ot_note, :lat, :lon)";
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':shift_code' => $shift_code,
            ':now_time' => $now_time,
            ':ot_flag' => $overtime_flag,
            ':ot_note' => $overtime_note,
            ':lat' => $user_lat,
            ':lon' => $user_lon,
            ':nik' => $nik,
            ':today' => $today,
        ]);

        json_ok(['message' => 'Absen Masuk ' . date('H:i', strtotime($now_time)) . ' berhasil!']);
    }

    if ($action === 'pulang') {
        $stmt_check = $conn->prepare("SELECT SHIFT_MASUK, SHIFT_PULANG, OVERTIME_BONUS_FLAG, SHIFT_JADWAL, OVERTIME_NOTES FROM T_ABSENSI WHERE KODESL = :nik AND TGL = :today");
        $stmt_check->execute([':nik' => $nik, ':today' => $today]);
        $existing = $stmt_check->fetch(PDO::FETCH_ASSOC);

        $has_masuk = ($existing && !empty($existing['SHIFT_MASUK']));
        $has_pulang = ($existing && !empty($existing['SHIFT_PULANG']));

        if (!$has_masuk || $has_pulang) {
            json_error('Absen Pulang gagal. Cek status masuk Anda.', 400);
        }

        $current_shift = $existing['SHIFT_JADWAL'];
        $overtime_flag = $existing['OVERTIME_BONUS_FLAG'] ?? 0;
        $overtime_note = $existing['OVERTIME_NOTES'] ?? '';
        $new_ot_flag = $overtime_flag;
        $new_ot_note = $overtime_note;

        if ($current_shift === 'S1' && strtotime($now_time) > strtotime('19:00:00')) {
            $new_ot_flag = 1;
            $new_ot_note .= (empty($overtime_note) ? '' : ' | ') . 'OT Pulang Malam (S1)';
        }

        $sql = "UPDATE T_ABSENSI SET 
                    SHIFT_PULANG = :now_time, 
                    OVERTIME_BONUS_FLAG = :ot_flag,
                    OVERTIME_NOTES = :ot_note,
                    LAST_LATITUDE = :lat, LAST_LONGITUDE = :lon
                WHERE KODESL = :nik AND TGL = :today";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':now_time' => $now_time,
            ':ot_flag' => $new_ot_flag,
            ':ot_note' => $new_ot_note,
            ':lat' => $user_lat,
            ':lon' => $user_lon,
            ':nik' => $nik,
            ':today' => $today,
        ]);

        json_ok(['message' => 'Absen Pulang ' . date('H:i', strtotime($now_time)) . ' berhasil!']);
    }

    json_error('Aksi tidak dikenal.', 400);
} catch (Exception $e) {
    json_error('Database Error: ' . $e->getMessage(), 500);
}
