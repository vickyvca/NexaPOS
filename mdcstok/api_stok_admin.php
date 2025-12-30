<?php
require_once __DIR__ . '/api_common.php';
require_roles(['admin']);

function column_exists(PDO $conn, string $table, string $column): bool {
    $sql = "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME=:t AND COLUMN_NAME=:c";
    $st = $conn->prepare($sql);
    $st->execute([':t' => $table, ':c' => $column]);
    return (bool)$st->fetchColumn();
}

function pick_column(PDO $conn, string $table, array $cands, ?string $fallback = null): ?string {
    foreach ($cands as $c) {
        if (column_exists($conn, $table, $c)) {
            return $c;
        }
    }
    return $fallback;
}

function escape_like(string $s): string {
    return strtr($s, ['\\' => '\\\\', '%' => '\\%', '_' => '\\_', '[' => '\\[']);
}

try {
    $vb_kode = pick_column($conn, 'V_BARANG', ['KODEBRG', 'KODE', 'KODE_BARANG']);
    $vb_nama = pick_column($conn, 'V_BARANG', ['NAMABRG', 'NAMA', 'NAMA_BARANG']);
    if (!$vb_kode || !$vb_nama) {
        json_error('Kolom kode/nama tidak ditemukan di V_BARANG.', 500);
    }
    $vb_artikel = pick_column($conn, 'V_BARANG', ['ARTIKELBRG', 'ARTIKEL'], null);

    $st_cols = [];
    foreach (['ST00', 'ST01', 'ST02', 'ST03', 'ST04'] as $st) {
        $st_cols[$st] = column_exists($conn, 'V_BARANG', $st);
    }

    $vb_retur = pick_column($conn, 'V_BARANG', ['RETUR', 'RETURFLAG', 'ADA_RETUR', 'STATUS_RETUR'], null);
    $vb_umur_num = pick_column($conn, 'V_BARANG', ['UMUR', 'UMUR_STOK', 'UMURHARI'], null);
    $vb_lastbuy_date = pick_column($conn, 'V_BARANG', ['TGLBELITERAKHIR', 'TGL_BELI_TERAKHIR', 'LASTBUY', 'LAST_BUY', 'LAST_PURCHASE_DATE'], null);

    $vb_hbeli = pick_column($conn, 'V_BARANG', ['HARGABELI', 'HGBELI', 'H_BELI'], null);
    $vb_hjual = pick_column($conn, 'V_BARANG', ['HARGAJUAL', 'HGJUAL', 'H_JUAL'], null);
    $vb_disc = pick_column($conn, 'V_BARANG', ['DISKON', 'DISK', 'DISC'], null);
} catch (Exception $e) {
    json_error('Schema error: ' . $e->getMessage(), 500);
}

$q = trim($_GET['q'] ?? '');
$stok_gt0 = isset($_GET['stok_gt0']) ? 1 : 0;
$retur = $_GET['retur'] ?? '';
$sort = $_GET['sort'] ?? 'baru';
$limit = max(1, (int)($_GET['limit'] ?? 100));

$stok_total_expr_parts = [];
foreach (['ST00', 'ST01', 'ST02', 'ST03', 'ST04'] as $st) {
    if (!empty($st_cols[$st])) {
        $stok_total_expr_parts[] = "ISNULL(v.$st,0)";
    }
}
$stok_total_expr = $stok_total_expr_parts ? implode(' + ', $stok_total_expr_parts) : "0";

$retur_truth_expr = $vb_retur
    ? "CASE WHEN TRY_CAST(v.$vb_retur AS INT)=1 OR UPPER(LTRIM(RTRIM(CAST(v.$vb_retur AS NVARCHAR(10))))) IN ('Y','YA','YES','TRUE','1') THEN 'Ya' ELSE 'Tidak' END"
    : "'Tidak'";

$umur_expr = null;
if ($vb_umur_num) {
    $umur_expr = "TRY_CAST(v.$vb_umur_num AS INT)";
} elseif ($vb_lastbuy_date) {
    $umur_expr = "DATEDIFF(DAY, TRY_CONVERT(date, v.$vb_lastbuy_date), CAST(GETDATE() AS date))";
}
$show_umur = $umur_expr !== null;

$sql = "
SELECT TOP $limit
    v.$vb_kode AS KODEBRG,
    RTRIM(LTRIM(v.$vb_nama)) AS NAMABRG,
    " . ($vb_artikel ? "RTRIM(LTRIM(v.$vb_artikel)) AS ARTIKELBRG," : "'-' AS ARTIKELBRG,") . "
    ($stok_total_expr) AS STOK_TOTAL,
    $retur_truth_expr AS STATUS_RETUR" .
    ($show_umur ? ", $umur_expr AS UMUR_HARI" : "") . "," .
    ($vb_hbeli ? "TRY_CAST(v.$vb_hbeli AS NUMERIC(18,2)) AS HARGA_BELI," : "NULL AS HARGA_BELI,") .
    ($vb_hjual ? "TRY_CAST(v.$vb_hjual AS NUMERIC(18,2)) AS HARGA_JUAL," : "NULL AS HARGA_JUAL,") .
    ($vb_disc ? "TRY_CAST(v.$vb_disc AS NUMERIC(18,4)) AS DISKON" : "NULL AS DISKON") . "
FROM V_BARANG v
WHERE 1=1
";

$order_clauses = [];
if ($q !== '') {
    $qEsc = escape_like($q);
    $p_sw = $qEsc . '%';
    $p_any = '%' . $qEsc . '%';
    $sql .= " AND (v.$vb_kode LIKE " . $conn->quote($p_sw) . " ESCAPE '\\'
               OR v.$vb_nama LIKE " . $conn->quote($p_any) . " ESCAPE '\\' " .
               ($vb_artikel ? " OR v.$vb_artikel LIKE " . $conn->quote($p_any) . " ESCAPE '\\' " : '') .
               " OR v.$vb_kode LIKE " . $conn->quote($p_any) . " ESCAPE '\\')";
    if ($sort === 'relevansi') {
        $order_clauses[] = "(CASE WHEN v.$vb_kode LIKE " . $conn->quote($p_sw) . "  ESCAPE '\\' THEN 1 ELSE 0 END) DESC";
        $order_clauses[] = "(CASE WHEN v.$vb_nama LIKE " . $conn->quote($p_any) . " ESCAPE '\\' THEN 1 ELSE 0 END) DESC";
        if ($vb_artikel) {
            $order_clauses[] = "(CASE WHEN v.$vb_artikel LIKE " . $conn->quote($p_any) . " ESCAPE '\\' THEN 1 ELSE 0 END) DESC";
        }
        $order_clauses[] = "(CASE WHEN v.$vb_kode LIKE " . $conn->quote($p_any) . " ESCAPE '\\' THEN 1 ELSE 0 END) DESC";
    }
}
if ($stok_gt0) {
    $sql .= " AND ($stok_total_expr) > 0 ";
}
if ($retur === 'ya') {
    $sql .= " AND ($retur_truth_expr) = 'Ya' ";
} elseif ($retur === 'tidak') {
    $sql .= " AND ($retur_truth_expr) = 'Tidak' ";
}

if ($sort === 'relevansi' && !empty($order_clauses)) {
    $sql .= " ORDER BY " . implode(", ", $order_clauses) . ", v.$vb_kode ";
} elseif ($sort === 'kode_asc') {
    $sql .= " ORDER BY v.$vb_kode ASC ";
} elseif ($sort === 'kode_desc') {
    $sql .= " ORDER BY v.$vb_kode DESC ";
} else {
    if ($show_umur) {
        $sql .= " ORDER BY CASE WHEN $umur_expr IS NULL THEN 1 ELSE 0 END ASC, $umur_expr ASC ";
    } else {
        $sql .= " ORDER BY v.$vb_kode DESC ";
    }
}

try {
    $stmt = $conn->query($sql);
    $items = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $diskon = $row['DISKON'];
        if ($diskon !== null) {
            $diskon = (float)$diskon;
            if ($diskon > 0 && $diskon <= 1) {
                $diskon *= 100;
            }
        }
        $items[] = [
            'kode' => $row['KODEBRG'],
            'nama' => $row['NAMABRG'],
            'artikel' => $row['ARTIKELBRG'] ?: '-',
            'stok' => (int)$row['STOK_TOTAL'],
            'hargaBeli' => $row['HARGA_BELI'] !== null ? (float)$row['HARGA_BELI'] : null,
            'hargaJual' => $row['HARGA_JUAL'] !== null ? (float)$row['HARGA_JUAL'] : null,
            'diskon' => $diskon,
            'umur' => $show_umur ? ($row['UMUR_HARI'] !== null ? (int)$row['UMUR_HARI'] : null) : null,
            'statusRetur' => $row['STATUS_RETUR'] === 'Ya' ? 'Ya' : 'Tidak',
        ];
    }
    json_ok(['data' => ['items' => $items]]);
} catch (Exception $e) {
    json_error('Query error: ' . $e->getMessage(), 500);
}
