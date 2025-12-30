<?php
require_once __DIR__ . '/api_common.php';
require_roles(['pegawai']);

$nik = $_SESSION['nik'] ?? '';
$bulan = $_GET['bulan'] ?? 'this';

if ($bulan === 'this') {
    $start = date('Y-m-01');
    $end = date('Y-m-d');
} else {
    $start = date('Y-m-01', strtotime($bulan . '-01'));
    $end = date('Y-m-t', strtotime($bulan . '-01'));
}

$stmt = $conn->prepare("
    SELECT KODEJN, KETJENIS, KODEBRG, NAMABRG, ARTIKELBRG, QTY, NETTO
    FROM V_JUAL
    WHERE KODESL = :nik AND TGL BETWEEN :start AND :end
    ORDER BY KODEJN, KODEBRG
");
$stmt->execute(['nik' => $nik, 'start' => $start, 'end' => $end]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$grouped = [];
$grand_total_qty = 0;
$grand_total_netto = 0;
foreach ($data as $row) {
    $kj = $row['KODEJN'];
    if (!isset($grouped[$kj])) {
        $grouped[$kj] = [
            'jenis' => $row['KETJENIS'] ?: "Jenis $kj",
            'items' => [],
            'subtotal_qty' => 0,
            'subtotal_netto' => 0,
        ];
    }
    $grouped[$kj]['items'][] = [
        'kodebrg' => $row['KODEBRG'],
        'namabrg' => $row['NAMABRG'],
        'artikelbrg' => $row['ARTIKELBRG'],
        'qty' => (float)$row['QTY'],
        'netto' => (float)$row['NETTO'],
    ];
    $grouped[$kj]['subtotal_qty'] += $row['QTY'];
    $grouped[$kj]['subtotal_netto'] += $row['NETTO'];
    $grand_total_qty += $row['QTY'];
    $grand_total_netto += $row['NETTO'];
}

json_ok([
    'data' => [
        'bulan' => $bulan,
        'start' => $start,
        'end' => $end,
        'grouped' => array_values($grouped),
        'grand' => [
            'total_qty' => $grand_total_qty,
            'total_netto' => $grand_total_netto,
        ],
    ],
]);
