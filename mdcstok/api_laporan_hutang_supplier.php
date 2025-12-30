<?php
require_once __DIR__ . '/api_common.php';
require_roles(['supplier']);

$kodesp = $_SESSION['kodesp'] ?? '';
if (!$kodesp) {
    json_error('Kode supplier tidak ditemukan.', 401);
}

$bulan = $_GET['bulan'] ?? date('Y-m');
$target_nobukti = $_GET['nobukti'] ?? '';

$stmt = $conn->prepare("
    SELECT H.NONOTA, B.TGL, B.NOBUKTI, S.KODESP, NAMASP, TGLJTO,
           CASE WHEN H.STATUS = 2 THEN 'LUNAS' ELSE 'BELUM' END AS STATUS,
           TOTALHTG, SISAHTG
    FROM HIS_HUTANG H
    INNER JOIN HIS_BELI B ON B.NONOTA = H.NONOTA
    INNER JOIN T_SUPLIER S ON S.KODESP = B.KODESP
    WHERE B.KODESP = :kodesp AND FORMAT(B.TGL, 'yyyy-MM') = :bulan
    ORDER BY B.TGL
");
$stmt->execute(['kodesp' => $kodesp, 'bulan' => $bulan]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$total_hutang = 0;
$total_sisa_hutang = 0;
$jumlah_belum_lunas = 0;

$rows = [];
foreach ($data as $row) {
    $total_hutang += (float)$row['TOTALHTG'];
    $total_sisa_hutang += (float)$row['SISAHTG'];
    if (strtoupper($row['STATUS']) !== 'LUNAS') {
        $jumlah_belum_lunas++;
    }
    $rows[] = [
        'nonota' => $row['NONOTA'] ?? '',
        'tgl' => $row['TGL'] ?? null,
        'nobukti' => $row['NOBUKTI'] ?? '',
        'tgljto' => $row['TGLJTO'] ?? null,
        'status' => $row['STATUS'] ?? '',
        'totalhtg' => (float)($row['TOTALHTG'] ?? 0),
        'sisahtg' => (float)($row['SISAHTG'] ?? 0),
        'isTarget' => ($target_nobukti !== '' && ($row['NOBUKTI'] ?? '') === $target_nobukti),
    ];
}

json_ok([
    'data' => [
        'bulan' => $bulan,
        'summary' => [
            'total_hutang' => $total_hutang,
            'total_sisa_hutang' => $total_sisa_hutang,
            'jumlah_belum_lunas' => $jumlah_belum_lunas,
        ],
        'rows' => $rows,
    ],
]);
