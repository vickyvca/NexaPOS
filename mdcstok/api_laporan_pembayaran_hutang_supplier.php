<?php
require_once __DIR__ . '/api_common.php';
require_roles(['supplier']);

$kodesp = $_SESSION['kodesp'] ?? '';
if (!$kodesp) {
    json_error('Kode supplier tidak ditemukan.', 401);
}

$tgl1 = $_GET['tgl1'] ?? date('Y-m-01');
$tgl2 = $_GET['tgl2'] ?? date('Y-m-d');
$requested_nonota = trim($_GET['nonota'] ?? '');
$requested_nobukti = trim($_GET['nobukti'] ?? '');

$filter_nonota = '';
if ($requested_nonota !== '') {
    $filter_nonota = $requested_nonota;
} elseif ($requested_nobukti !== '') {
    try {
        $q = $conn->prepare("SELECT NONOTA FROM HIS_BELI WHERE KODESP = :k AND NOBUKTI = :nb");
        $q->execute(['k' => $kodesp, 'nb' => $requested_nobukti]);
        $r = $q->fetch(PDO::FETCH_ASSOC);
        if ($r && !empty($r['NONOTA'])) {
            $filter_nonota = $r['NONOTA'];
        }
    } catch (PDOException $e) {
    }
}

$params = ['kodesp' => $kodesp];
if ($filter_nonota !== '') {
    $sql = "
        SELECT 
            H.NONOTA, 
            H.NOBUKTI, 
            S.NAMASP, 
            K.KET AS JENISTRANS, 
            H.TGL, 
            D.TGL AS TGLJT, 
            D.JENIS, 
            D.NOTABAYAR,
            CASE WHEN D.JENIS = 1 THEN D.NILAI ELSE 0 END AS KREDIT,
            CASE WHEN D.JENIS <> 1 THEN D.NILAI ELSE 0 END AS DEBET
        FROM HIS_BAYARHUTANG H
        INNER JOIN HIS_DTBAYARHUTANG D ON D.NONOTA = H.NONOTA
        INNER JOIN T_KETHUTANG K ON K.JENIS = D.JENIS
        INNER JOIN T_SUPLIER S ON S.KODESP = H.KODESP
        WHERE H.KODESP = :kodesp AND H.NONOTA = :nonota
        ORDER BY H.NONOTA, H.TGL
    ";
    $params['nonota'] = $filter_nonota;
} else {
    $sql = "
        SELECT 
            H.NONOTA, 
            H.NOBUKTI, 
            S.NAMASP, 
            K.KET AS JENISTRANS, 
            H.TGL, 
            D.TGL AS TGLJT, 
            D.JENIS, 
            D.NOTABAYAR,
            CASE WHEN D.JENIS = 1 THEN D.NILAI ELSE 0 END AS KREDIT,
            CASE WHEN D.JENIS <> 1 THEN D.NILAI ELSE 0 END AS DEBET
        FROM HIS_BAYARHUTANG H
        INNER JOIN HIS_DTBAYARHUTANG D ON D.NONOTA = H.NONOTA
        INNER JOIN T_KETHUTANG K ON K.JENIS = D.JENIS
        INNER JOIN T_SUPLIER S ON S.KODESP = H.KODESP
        WHERE H.KODESP = :kodesp AND H.TGL BETWEEN :tgl1 AND :tgl2
        ORDER BY H.NONOTA, H.TGL
    ";
    $params['tgl1'] = $tgl1;
    $params['tgl2'] = $tgl2;
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$grouped = [];
foreach ($rows as $row) {
    $nota = $row['NONOTA'];
    if (!isset($grouped[$nota])) {
        $grouped[$nota] = [
            'nonota' => $nota,
            'nobukti' => $row['NOBUKTI'],
            'supplier' => $row['NAMASP'],
            'tanggal' => $row['TGL'],
            'rows' => [],
            'total_debet' => 0,
            'total_kredit' => 0,
        ];
    }
    $grouped[$nota]['rows'][] = [
        'jenis_trans' => $row['JENISTRANS'],
        'nota_bayar' => $row['NOTABAYAR'],
        'tgl_jt' => $row['TGLJT'],
        'jenis' => (int)$row['JENIS'],
        'debet' => (float)($row['DEBET'] ?? 0),
        'kredit' => (float)($row['KREDIT'] ?? 0),
    ];
    $grouped[$nota]['total_debet'] += $row['DEBET'] ?? 0;
    $grouped[$nota]['total_kredit'] += $row['KREDIT'] ?? 0;
}

json_ok([
    'data' => [
        'filters' => [
            'tgl1' => $tgl1,
            'tgl2' => $tgl2,
            'nonota' => $filter_nonota,
        ],
        'groups' => array_values($grouped),
    ],
]);
