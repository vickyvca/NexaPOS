<?php
require_once __DIR__ . '/api_common.php';
require_roles(['pegawai']);

$nik = $_SESSION['nik'] ?? '';
if (!$nik) {
    json_error('NIK tidak ditemukan.', 401);
}

$end = $_GET['end'] ?? date('Y-m-d');
$start = $_GET['start'] ?? date('Y-m-d', strtotime('-30 days', strtotime($end)));

try {
    $stmt = $conn->prepare(
        "SELECT TGL, SHIFT_JADWAL, SHIFT_MASUK, SHIFT_PULANG, STATUS_HARI, OVERTIME_BONUS_FLAG
         FROM T_ABSENSI
         WHERE KODESL = :nik AND TGL BETWEEN :start AND :end
         ORDER BY TGL DESC"
    );
    $stmt->execute([':nik' => $nik, ':start' => $start, ':end' => $end]);
    $rows = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rows[] = [
            'tanggal' => date('Y-m-d', strtotime($row['TGL'])),
            'shift' => $row['SHIFT_JADWAL'] ?? 'N/A',
            'masuk' => $row['SHIFT_MASUK'] ? date('H:i', strtotime($row['SHIFT_MASUK'])) : null,
            'pulang' => $row['SHIFT_PULANG'] ? date('H:i', strtotime($row['SHIFT_PULANG'])) : null,
            'status' => $row['STATUS_HARI'] ?? 'BELUM ABSEN',
            'lembur' => !empty($row['OVERTIME_BONUS_FLAG']),
        ];
    }

    json_ok([
        'data' => [
            'start' => $start,
            'end' => $end,
            'rows' => $rows,
        ],
    ]);
} catch (PDOException $e) {
    json_error('DB Query Error (T_ABSENSI): ' . $e->getMessage(), 500);
}
