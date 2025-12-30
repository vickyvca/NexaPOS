<?php
require_once __DIR__ . '/api_common.php';
require_roles(['pegawai']);

$nik = $_SESSION['nik'] ?? '';
if (!$nik) {
    json_error('NIK tidak ditemukan.', 401);
}

$ym = $_GET['bulan'] ?? date('Y-m');
$start = $ym . '-01';
$end = date('Y-m-t', strtotime($start));

try {
    $stmt = $conn->prepare(
        "SELECT STATUS_HARI, OVERTIME_BONUS_FLAG
         FROM T_ABSENSI
         WHERE KODESL = :nik AND TGL BETWEEN :start AND :end"
    );
    $stmt->execute([':nik' => $nik, ':start' => $start, ':end' => $end]);
    $hadir = 0;
    $terlambat = 0;
    $libur = 0;
    $lembur = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $status = strtoupper((string)($row['STATUS_HARI'] ?? ''));
        if ($status === 'HADIR' || $status === 'HADIR_MANUAL') {
            $hadir++;
        } elseif ($status === 'TERLAMBAT') {
            $terlambat++;
        } elseif (in_array($status, ['LIBUR', 'CUTI', 'SAKIT', 'IZIN'], true)) {
            $libur++;
        }
        if (!empty($row['OVERTIME_BONUS_FLAG'])) {
            $lembur++;
        }
    }

    json_ok([
        'data' => [
            'bulan' => $ym,
            'hadir' => $hadir,
            'terlambat' => $terlambat,
            'libur' => $libur,
            'lembur' => $lembur,
        ],
    ]);
} catch (PDOException $e) {
    json_error('DB Query Error (T_ABSENSI): ' . $e->getMessage(), 500);
}
