<?php
require_once __DIR__ . '/api_common.php';
require_roles(['supplier']);

$kodesp = $_SESSION['kodesp'] ?? '';
if (!$kodesp) {
    json_error('Kode supplier tidak ditemukan.', 401);
}

$kodejn = $_GET['kodejn'] ?? 'all';
$bulan = $_GET['bulan'] ?? date('Y-m');
if ($bulan === date('Y-m')) {
    $bulan = 'this';
}

if ($bulan === 'this') {
    $start = date('Y-m-01');
    $end = date('Y-m-d');
} else {
    $start = date('Y-m-01', strtotime($bulan . '-01'));
    $end = date('Y-m-t', strtotime($bulan . '-01'));
}

$filter = "KODESP = :kodesp AND TGL BETWEEN :start AND :end AND QTY > 0";
$params = ['kodesp' => $kodesp, 'start' => $start, 'end' => $end];
if ($kodejn !== 'all') {
    $filter .= " AND KODEJN = :kodejn";
    $params['kodejn'] = $kodejn;
}

$stmt = $conn->prepare("
    SELECT KODEJN, KETJENIS, KODEBRG, NAMABRG, ARTIKELBRG, SUM(QTY) AS QTY
    FROM V_JUAL
    WHERE $filter
    GROUP BY KODEJN, KETJENIS, KODEBRG, NAMABRG, ARTIKELBRG
    ORDER BY KODEJN, KODEBRG
");
$stmt->execute($params);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$grouped = [];
foreach ($data as $row) {
    $kj = $row['KODEJN'];
    if (!isset($grouped[$kj])) {
        $grouped[$kj] = ['jenis' => $row['KETJENIS'] ?: "Jenis $kj", 'items' => []];
    }
    $grouped[$kj]['items'][] = [
        'kodebrg' => $row['KODEBRG'],
        'namabrg' => $row['NAMABRG'],
        'artikelbrg' => $row['ARTIKELBRG'],
        'qty' => (float)$row['QTY'],
    ];
}

$jenis_list_stmt = $conn->prepare("SELECT DISTINCT KODEJN, KETJENIS FROM V_JUAL WHERE KODESP = :kodesp ORDER BY KETJENIS");
$jenis_list_stmt->execute(['kodesp' => $kodesp]);
$jenis_list = $jenis_list_stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

json_ok([
    'data' => [
        'bulan' => $bulan,
        'start' => $start,
        'end' => $end,
        'kodejn' => $kodejn,
        'jenis' => array_map(function ($row) {
            return ['kodejn' => $row['KODEJN'], 'ketjenis' => $row['KETJENIS']];
        }, $jenis_list),
        'grouped' => array_values($grouped),
    ],
]);
