<?php
require_once __DIR__ . '/api_common.php';
require_roles(['admin']);

$tgl1 = $_GET['tgl1'] ?? date('Y-m-01');
$tgl2 = $_GET['tgl2'] ?? date('Y-m-d');
$kodesp = $_GET['kodesp'] ?? 'all';
$kodejn = $_GET['kodejn'] ?? 'all';

$suppliers = $conn->query("SELECT KODESP, NAMASP FROM T_SUPLIER ORDER BY KODESP ASC")->fetchAll(PDO::FETCH_ASSOC);
$item_types = $conn->query("SELECT DISTINCT KODEJN, KETJENIS FROM V_JUAL WHERE KETJENIS IS NOT NULL AND KETJENIS <> '' ORDER BY KODEJN ASC")->fetchAll(PDO::FETCH_ASSOC);

$filter = "TGL BETWEEN :tgl1 AND :tgl2";
$params = ['tgl1' => $tgl1, 'tgl2' => $tgl2];
if ($kodesp !== 'all') {
    $filter .= " AND KODESP = :kodesp";
    $params['kodesp'] = $kodesp;
}
if ($kodejn !== 'all') {
    $filter .= " AND KODEJN = :kodejn";
    $params['kodejn'] = $kodejn;
}

$stmt = $conn->prepare("
    SELECT KODESP, NAMASP, KODEJN, KETJENIS, KODEBRG, NAMABRG, ARTIKELBRG, SUM(QTY) AS TOTAL_QTY, SUM(NETTO) AS TOTAL_NETTO
    FROM V_JUAL
    WHERE $filter
    GROUP BY KODESP, NAMASP, KODEJN, KETJENIS, KODEBRG, NAMABRG, ARTIKELBRG
    HAVING SUM(QTY) > 0
    ORDER BY NAMASP, KETJENIS, NAMABRG
");
$stmt->execute($params);

$grouped_data = [];
$grand_total_qty = 0;
$grand_total_netto = 0;

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $sp_key = $row['KODESP'];
    $jn_key = $row['KODEJN'];

    if (!isset($grouped_data[$sp_key])) {
        $grouped_data[$sp_key] = [
            'supplier_name' => $row['NAMASP'],
            'jenis' => [],
            'total_qty' => 0,
            'total_netto' => 0,
        ];
    }
    if (!isset($grouped_data[$sp_key]['jenis'][$jn_key])) {
        $grouped_data[$sp_key]['jenis'][$jn_key] = [
            'jenis_name' => $row['KETJENIS'] ?: 'Lainnya',
            'items' => [],
            'subtotal_qty' => 0,
            'subtotal_netto' => 0,
        ];
    }

    $grouped_data[$sp_key]['jenis'][$jn_key]['items'][] = [
        'kodebrg' => $row['KODEBRG'],
        'namabrg' => $row['NAMABRG'],
        'artikelbrg' => $row['ARTIKELBRG'],
        'total_qty' => (float)$row['TOTAL_QTY'],
        'total_netto' => (float)$row['TOTAL_NETTO'],
    ];
    $grouped_data[$sp_key]['jenis'][$jn_key]['subtotal_qty'] += $row['TOTAL_QTY'];
    $grouped_data[$sp_key]['jenis'][$jn_key]['subtotal_netto'] += $row['TOTAL_NETTO'];
    $grouped_data[$sp_key]['total_qty'] += $row['TOTAL_QTY'];
    $grouped_data[$sp_key]['total_netto'] += $row['TOTAL_NETTO'];
    $grand_total_qty += $row['TOTAL_QTY'];
    $grand_total_netto += $row['TOTAL_NETTO'];
}

json_ok([
    'data' => [
        'filters' => [
            'tgl1' => $tgl1,
            'tgl2' => $tgl2,
            'kodesp' => $kodesp,
            'kodejn' => $kodejn,
        ],
        'suppliers' => array_map(function ($row) {
            return ['kodesp' => $row['KODESP'], 'namasp' => $row['NAMASP']];
        }, $suppliers),
        'itemTypes' => array_map(function ($row) {
            return ['kodejn' => $row['KODEJN'], 'ketjenis' => $row['KETJENIS']];
        }, $item_types),
        'grouped' => array_values($grouped_data),
        'grand' => [
            'total_qty' => $grand_total_qty,
            'total_netto' => $grand_total_netto,
        ],
    ],
]);
