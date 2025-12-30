<?php
require_once __DIR__ . '/api_common.php';
require_once __DIR__ . '/payroll/payroll_lib.php';
require_roles(['admin']);

$date = $_GET['date'] ?? date('Y-m-d');

function get_sales_name(PDO $conn, string $nik): string {
    try {
        $stmt = $conn->prepare("SELECT TOP 1 NAMASL FROM T_SALES WHERE KODESL = :nik");
        $stmt->execute([':nik' => $nik]);
        return $stmt->fetchColumn() ?: $nik;
    } catch (PDOException $e) {
        return $nik;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payload = json_decode(file_get_contents('php://input'), true) ?? [];
    $nik = trim((string)($payload['nik'] ?? ''));
    $tanggal = trim((string)($payload['tanggal'] ?? ''));
    $status = strtoupper(trim((string)($payload['status'] ?? '')));
    $masuk = isset($payload['masuk']) && $payload['masuk'] !== '' ? date('H:i:s', strtotime($payload['masuk'])) : null;
    $pulang = isset($payload['pulang']) && $payload['pulang'] !== '' ? date('H:i:s', strtotime($payload['pulang'])) : null;

    if ($nik === '' || $tanggal === '' || $status === '') {
        json_error('nik, tanggal, dan status wajib diisi.', 400);
    }

    $expected_shift = get_expected_shift_for_date($nik, $tanggal) ?? 'N/A';

    try {
        $stmt = $conn->prepare("UPDATE T_ABSENSI SET STATUS_HARI = :status, SHIFT_MASUK = :masuk, SHIFT_PULANG = :pulang WHERE KODESL = :nik AND TGL = :tgl");
        $stmt->execute([
            ':status' => $status,
            ':masuk' => $masuk,
            ':pulang' => $pulang,
            ':nik' => $nik,
            ':tgl' => $tanggal,
        ]);
        if ($stmt->rowCount() === 0) {
            $insert = $conn->prepare("INSERT INTO T_ABSENSI (KODESL, TGL, STATUS_HARI, SHIFT_JADWAL, SHIFT_MASUK, SHIFT_PULANG) VALUES (:nik, :tgl, :status, :shift, :masuk, :pulang)");
            $insert->execute([
                ':nik' => $nik,
                ':tgl' => $tanggal,
                ':status' => $status,
                ':shift' => $expected_shift,
                ':masuk' => $masuk,
                ':pulang' => $pulang,
            ]);
        }
        json_ok(['message' => 'Status absensi berhasil disimpan.']);
    } catch (PDOException $e) {
        json_error('Gagal menyimpan absensi: ' . $e->getMessage(), 500);
    }
}

$selected_employees = get_selected_employees();
$expected_shift_map = [];
foreach ($selected_employees as $spg) {
    $expected_shift_map[$spg['nik']] = get_expected_shift_for_date($spg['nik'], $date);
}

$absen_data_map = [];
if (!empty($selected_employees)) {
    $niks = array_column($selected_employees, 'nik');
    $placeholders = implode(',', array_fill(0, count($niks), '?'));
    $sql = "SELECT KODESL, SHIFT_MASUK, SHIFT_PULANG, STATUS_HARI, SHIFT_JADWAL, OVERTIME_BONUS_FLAG
            FROM T_ABSENSI 
            WHERE TGL = ? AND KODESL IN ($placeholders)";
    $params = array_merge([$date], $niks);
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $absen_data_map[$row['KODESL']] = $row;
    }
}

$rows = [];
foreach ($selected_employees as $spg) {
    $nik = $spg['nik'];
    $absen = $absen_data_map[$nik] ?? null;
    $nama = get_sales_name($conn, $nik);
    $expected_shift = $expected_shift_map[$nik] ?? null;

    $status_current = 'BELUM ABSEN';
    $jadwal_code = $expected_shift ?? 'N/A';
    $masuk_str = null;
    $pulang_str = null;
    $has_ot = false;

    if ($absen) {
        $status_current = $absen['STATUS_HARI'] ?? 'BELUM ABSEN';
        $jadwal_code = $absen['SHIFT_JADWAL'] ?: ($expected_shift ?? 'N/A');
        $masuk_str = $absen['SHIFT_MASUK'] ? date('H:i', strtotime($absen['SHIFT_MASUK'])) : null;
        $pulang_str = $absen['SHIFT_PULANG'] ? date('H:i', strtotime($absen['SHIFT_PULANG'])) : null;
        $has_ot = !empty($absen['OVERTIME_BONUS_FLAG']);
    }

    $rows[] = [
        'nik' => $nik,
        'nama' => $nama,
        'tanggal' => $date,
        'masuk' => $masuk_str,
        'pulang' => $pulang_str,
        'status' => strtolower((string)$status_current),
        'lembur' => $has_ot,
        'shift' => $jadwal_code ?: 'N/A',
    ];
}

json_ok([
    'data' => [
        'date' => $date,
        'rows' => $rows,
    ],
]);
