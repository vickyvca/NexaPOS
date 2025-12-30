<?php
require_once __DIR__ . '/api_common.php';
require_roles(['supplier']);

$kodesp = $_SESSION['kodesp'] ?? '';
if (!$kodesp) {
    json_error('Kode supplier tidak ditemukan.', 401);
}

$keyword = $_GET['keyword'] ?? '';
$umur_op = $_GET['umur_op'] ?? '>';
$umur_val = $_GET['umur_val'] ?? '';
$retur = $_GET['retur'] ?? 'all';
$kodejn = $_GET['kodejn'] ?? 'all';
$sort = $_GET['sort'] ?? 'umur_asc';
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}
$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 100;
$offset = ($page - 1) * $limit;

$filter = "KODESP = :kodesp";
$params = ['kodesp' => $kodesp];

if ($keyword !== '') {
    $filter .= " AND (KODEBRG LIKE :kw1 OR ARTIKELBRG LIKE :kw2 OR NAMABRG LIKE :kw3)";
    $params['kw1'] = "%$keyword%";
    $params['kw2'] = "%$keyword%";
    $params['kw3'] = "%$keyword%";
}
if (is_numeric($umur_val) && $umur_val !== '') {
    $filter .= " AND UMUR $umur_op :umur_val";
    $params['umur_val'] = (int)$umur_val;
}
if ($retur === '1') {
    $filter .= " AND RETUR = 1";
} elseif ($retur === '0') {
    $filter .= " AND (RETUR = 0 OR RETUR IS NULL)";
}
if ($kodejn !== 'all') {
    $filter .= " AND KODEJN = :kodejn";
    $params['kodejn'] = $kodejn;
}

$orderBy = "ORDER BY KODEJN ASC, UMUR ASC";
if ($sort === 'umur_asc') {
    $orderBy = "ORDER BY UMUR ASC, KODEBRG ASC";
} elseif ($sort === 'umur_desc') {
    $orderBy = "ORDER BY UMUR DESC, KODEBRG ASC";
}

$countSql = "SELECT COUNT(*) FROM V_BARANG WHERE $filter";
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$total_records = (int)$countStmt->fetchColumn();
$total_pages = max(1, (int)ceil($total_records / $limit));

$pagination_clause = "OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";
$sql = "SELECT KODEJN, KETJENIS, KODEBRG, ARTIKELBRG, NAMABRG, UMUR, RETUR, ST00, ST01, ST02, ST03, ST04 
        FROM V_BARANG 
        WHERE $filter 
        $orderBy $pagination_clause";

$stmt = $conn->prepare($sql);
$params['offset'] = $offset;
$params['limit'] = $limit;
$stmt->bindParam(':offset', $params['offset'], PDO::PARAM_INT);
$stmt->bindParam(':limit', $params['limit'], PDO::PARAM_INT);
foreach ($params as $key => &$val) {
    if ($key !== 'offset' && $key !== 'limit') {
        $stmt->bindParam(":$key", $val);
    }
}
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$grouped = [];
foreach ($data as $row) {
    $kj = $row['KODEJN'];
    if (!isset($grouped[$kj])) {
        $grouped[$kj] = ['jenis' => $row['KETJENIS'] ?: "Jenis $kj", 'items' => []];
    }
    $total = (int)$row['ST00'] + (int)$row['ST01'] + (int)$row['ST02'] + (int)$row['ST03'] + (int)$row['ST04'];
    $grouped[$kj]['items'][] = [
        'kodebrg' => $row['KODEBRG'],
        'artikelbrg' => $row['ARTIKELBRG'],
        'namabrg' => $row['NAMABRG'],
        'umur' => (int)$row['UMUR'],
        'retur' => (int)($row['RETUR'] ?? 0),
        'stok_total' => $total,
    ];
}

$jenis_list_stmt = $conn->prepare("SELECT DISTINCT KODEJN, KETJENIS FROM V_BARANG WHERE KODESP = :kodesp ORDER BY KETJENIS");
$jenis_list_stmt->execute(['kodesp' => $kodesp]);
$jenis_list = $jenis_list_stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

json_ok([
    'data' => [
        'filters' => [
            'keyword' => $keyword,
            'umur_op' => $umur_op,
            'umur_val' => $umur_val,
            'retur' => $retur,
            'kodejn' => $kodejn,
            'sort' => $sort,
            'page' => $page,
        ],
        'jenis' => array_map(function ($row) {
            return ['kodejn' => $row['KODEJN'], 'ketjenis' => $row['KETJENIS']];
        }, $jenis_list),
        'grouped' => array_values($grouped),
        'pagination' => [
            'page' => $page,
            'pageSize' => $limit,
            'totalPages' => $total_pages,
            'totalRecords' => $total_records,
        ],
    ],
]);
