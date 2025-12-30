<?php
require_once __DIR__ . '/api_common.php';
require_roles(['admin']);

function get_omzet_by_range(PDO $conn, string $start, string $end, ?array $storeCodes = null, ?string $storeNameLike = null): float {
    try {
        $sql = "SELECT SUM(NETTO) as total FROM V_JUAL WHERE TGL BETWEEN :s AND :e";
        $params = [':s' => $start, ':e' => $end];
        if (!empty($storeCodes)) {
            $in = implode(',', array_fill(0, count($storeCodes), '?'));
            $sql .= " AND KODESP IN ($in)";
            foreach ($storeCodes as $c) {
                $params[] = $c;
            }
        }
        if ($storeNameLike) {
            $sql .= " AND NAMASP LIKE ?";
            $params[] = $storeNameLike;
        }
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)($result['total'] ?? 0);
    } catch (PDOException $e) {
        return 0;
    }
}

function get_supplier_ranking(PDO $conn, string $start, string $end): array {
    try {
        $sql = "SELECT KODESP, NAMASP, SUM(NETTO) AS TOTAL_NETTO, SUM(QTY) AS TOTAL_QTY
                FROM V_JUAL
                WHERE TGL BETWEEN :s AND :e
                GROUP BY KODESP, NAMASP
                ORDER BY SUM(NETTO) DESC";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':s' => $start, ':e' => $end]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        return [];
    }
}

$today = date('Y-m-d');
$ym_now = date('Y-m');

$mtd_ym = $_GET['mtd_month'] ?? $ym_now;
$is_current_month = ($mtd_ym === $ym_now);
$mtd_start = $mtd_ym . '-01';
$mtd_end_full = date('Y-m-t', strtotime($mtd_start));
$mtd_end = $is_current_month ? $today : $mtd_end_full;

$omzet_hari_ini = get_omzet_by_range($conn, $today, $today);
$omzet_bulan_ini = get_omzet_by_range($conn, $mtd_start, $mtd_end);

$target_total_bulan_ini = 0;
if (file_exists(__DIR__ . '/target_manual.json')) {
    $tj = json_decode(file_get_contents(__DIR__ . '/target_manual.json'), true);
    if (is_array($tj)) {
        if (isset($tj[$ym_now]['total'])) {
            $target_total_bulan_ini = (float)$tj[$ym_now]['total'];
        } elseif (isset($tj['total'])) {
            $target_total_bulan_ini = (float)$tj['total'];
        }
    }
}

$omzet_minggu_ini = get_omzet_by_range($conn, date('Y-m-d', strtotime('-6 days')), $today);
$omzet_minggu_lalu = get_omzet_by_range($conn, date('Y-m-d', strtotime('-13 days')), date('Y-m-d', strtotime('-7 days')));

$omzet_mtd_ini = get_omzet_by_range($conn, $mtd_start, $mtd_end);
$prev_mtd_ym = date('Y-m', strtotime($mtd_start . ' -1 month'));
$prev_mtd_start = $prev_mtd_ym . '-01';
$prev_mtd_days = (int)date('t', strtotime($prev_mtd_start));
$cap_day = (int)date('d', strtotime($mtd_end));
$prev_mtd_end = $prev_mtd_ym . '-' . str_pad(min($cap_day, $prev_mtd_days), 2, '0', STR_PAD_LEFT);
$omzet_mtd_lalu = get_omzet_by_range($conn, $prev_mtd_start, $prev_mtd_end);

$prev_year_start = date('Y-m-01', strtotime($mtd_start . ' -1 year'));
$prev_year_end_month = date('Y-m-t', strtotime($prev_year_start));
$target_day = (int)date('d', strtotime($mtd_end));
$prev_year_end_dt = new DateTime($prev_year_start);
$prev_year_end_dt->modify('+' . ($target_day - 1) . ' days');
$prev_month_end_dt = new DateTime($prev_year_end_month);
if ($prev_year_end_dt > $prev_month_end_dt) {
    $prev_year_end_dt = $prev_month_end_dt;
}
$prev_year_end = $prev_year_end_dt->format('Y-m-d');
$omzet_mtd_prev_year = get_omzet_by_range($conn, $prev_year_start, $prev_year_end);

$ytd_start_now = date('Y-01-01');
$omzet_ytd_ini = get_omzet_by_range($conn, $ytd_start_now, $today);
$prev_ytd_start = date('Y-01-01', strtotime('-1 year'));
$day_of_year = (int)date('z');
$prev_ytd_end_dt = new DateTime($prev_ytd_start);
$prev_ytd_end_dt->modify('+' . $day_of_year . ' days');
$prev_year_dec31 = new DateTime(date('Y-12-31', strtotime('-1 year')));
if ($prev_ytd_end_dt > $prev_year_dec31) {
    $prev_ytd_end_dt = $prev_year_dec31;
}
$prev_ytd_end = $prev_ytd_end_dt->format('Y-m-d');
$adj_branch_codes = ['C'];
$adj_branch_name_like = '%PEMUDA%';
$prev_part1_end = date('Y-05-31', strtotime('-1 year'));
$prev_part2_start = date('Y-06-01', strtotime('-1 year'));

$omzet_ytd_prev_year = 0;
$range1_start = $prev_ytd_start;
$range1_end = min($prev_part1_end, $prev_ytd_end);
if ($range1_start <= $range1_end) {
    $omzet_ytd_prev_year += get_omzet_by_range($conn, $range1_start, $range1_end);
}
if ($prev_ytd_end >= $prev_part2_start) {
    $range2_start = $prev_part2_start;
    $range2_end = $prev_ytd_end;
    $omzet_ytd_prev_year += get_omzet_by_range($conn, $range2_start, $range2_end, $adj_branch_codes, $adj_branch_name_like);
}

$rank_mode = $_GET['rank_mode'] ?? 'MTD';
$rank_month = $_GET['rank_month'] ?? $mtd_ym;
$rank_year = substr($rank_month, 0, 4);
$rank_is_current_month = ($rank_month === $ym_now);
if ($rank_mode === 'YTD') {
    $rank_start = $rank_year . '-01-01';
    $rank_month_end = date('Y-m-t', strtotime($rank_month . '-01'));
    if ($rank_year === date('Y') && $today < $rank_month_end) {
        $rank_end = $today;
    } else {
        $rank_end = $rank_month_end;
    }
} else {
    $rank_start = $rank_month . '-01';
    $rank_end_full = date('Y-m-t', strtotime($rank_start));
    $rank_end = $rank_is_current_month ? $today : $rank_end_full;
}

$supplier_ranking_all = get_supplier_ranking($conn, $rank_start, $rank_end);
$rank_page = max(1, (int)($_GET['rank_page'] ?? 1));
$page_size = max(1, (int)($_GET['page_size'] ?? 10));
$total_suppliers = count($supplier_ranking_all);
$total_pages = max(1, (int)ceil($total_suppliers / $page_size));
$offset = ($rank_page - 1) * $page_size;
$supplier_ranking = array_slice($supplier_ranking_all, $offset, $page_size);

json_ok([
    'data' => [
        'stats' => [
            'omzetHariIni' => $omzet_hari_ini,
            'omzetBulanIni' => $omzet_bulan_ini,
            'targetBulanIni' => $target_total_bulan_ini,
            'omzetMingguIni' => $omzet_minggu_ini,
            'omzetMingguLalu' => $omzet_minggu_lalu,
            'omzetMTDIni' => $omzet_mtd_ini,
            'omzetMTDLalu' => $omzet_mtd_lalu,
            'omzetMTDPrevYear' => $omzet_mtd_prev_year,
            'omzetYTDIni' => $omzet_ytd_ini,
            'omzetYTDPrevYear' => $omzet_ytd_prev_year,
        ],
        'supplierRanking' => array_map(function ($row) {
            return [
                'kodesp' => $row['KODESP'] ?? '',
                'namasp' => $row['NAMASP'] ?? '',
                'totalNetto' => (float)($row['TOTAL_NETTO'] ?? 0),
                'totalQty' => (float)($row['TOTAL_QTY'] ?? 0),
            ];
        }, $supplier_ranking),
        'supplierPagination' => [
            'page' => $rank_page,
            'pageSize' => $page_size,
            'totalItems' => $total_suppliers,
            'totalPages' => $total_pages,
        ],
    ],
]);
