<?php
require_once __DIR__ . '/api_common.php';
require_roles(['admin']);

$selected_supplier_id = $_GET['supplier_id'] ?? $_POST['supplier_id'] ?? null;
$filter_category = $_GET['category'] ?? null;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 15;

$suppliers = [];
try {
    $supplier_stmt = $conn->query("SELECT KODESP, NAMASP FROM T_SUPLIER ORDER BY NAMASP ASC");
    $suppliers = $supplier_stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    json_error('Gagal mengambil data supplier: ' . $e->getMessage(), 500);
}

$analysis_results = null;
if (!empty($selected_supplier_id)) {
    $selected_supplier_name = null;
    foreach ($suppliers as $sp) {
        if ($sp['KODESP'] == $selected_supplier_id) {
            $selected_supplier_name = $sp['NAMASP'];
            break;
        }
    }

    $end_date = date('Y-m-d');
    $start_date = date('Y-m-d', strtotime('-90 days'));

    $params = [
        ':kodesp' => $selected_supplier_id,
        ':start_date' => $start_date,
        ':end_date' => $end_date,
    ];
    $item_filter_params = $params;

    try {
        $item_where_clause = "j.KODESP = :kodesp AND j.TGL BETWEEN :start_date AND :end_date";
        if ($filter_category) {
            $item_where_clause .= " AND j.KETJENIS = :category";
            $item_filter_params[':category'] = $filter_category;
        }

        $count_query = "SELECT COUNT(DISTINCT j.KODEBRG) FROM V_JUAL j WHERE $item_where_clause";
        $count_stmt = $conn->prepare($count_query);
        $count_stmt->execute($item_filter_params);
        $total_records = (int)$count_stmt->fetchColumn();
        $total_pages = max(1, (int)ceil($total_records / $limit));
        $offset = max(0, ($page - 1) * $limit);

        $top_items_query = "
            SELECT j.KODEBRG, j.NAMABRG, j.ARTIKELBRG, SUM(j.QTY) as total_terjual,
                   ISNULL((SELECT SUM(ST00 + ST01 + ST02 + ST03 + ST04) FROM T_BARANG b WHERE b.KODEBRG = j.KODEBRG), 0) as sisa_stok
            FROM V_JUAL j
            WHERE $item_where_clause
            GROUP BY j.KODEBRG, j.NAMABRG, j.ARTIKELBRG
            ORDER BY total_terjual DESC
            OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY
        ";
        $stmt_items = $conn->prepare($top_items_query);
        foreach ($item_filter_params as $key => $val) {
            $stmt_items->bindValue($key, $val);
        }
        $stmt_items->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt_items->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt_items->execute();
        $top_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $top_categories_query = "SELECT TOP 5 KETJENIS, SUM(QTY) as total_terjual FROM V_JUAL WHERE KODESP = :kodesp AND TGL BETWEEN :start_date AND :end_date AND KETJENIS IS NOT NULL AND KETJENIS <> '' GROUP BY KETJENIS ORDER BY total_terjual DESC";
        $stmt_cats = $conn->prepare($top_categories_query);
        $stmt_cats->execute($params);
        $top_categories = $stmt_cats->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $top_articles_query = "
            SELECT TOP 5 
                CASE 
                    WHEN CHARINDEX('.', ARTIKELBRG, CHARINDEX('.', ARTIKELBRG) + 1) > 0 THEN LEFT(ARTIKELBRG, CHARINDEX('.', ARTIKELBRG, CHARINDEX('.', ARTIKELBRG) + 1) - 1)
                    ELSE LEFT(ARTIKELBRG, 7)
                END as article_group, 
                SUM(QTY) as total_terjual
            FROM V_JUAL
            WHERE KODESP = :kodesp AND TGL BETWEEN :start_date AND :end_date AND ARTIKELBRG IS NOT NULL AND ARTIKELBRG <> ''
            GROUP BY CASE WHEN CHARINDEX('.', ARTIKELBRG, CHARINDEX('.', ARTIKELBRG) + 1) > 0 THEN LEFT(ARTIKELBRG, CHARINDEX('.', ARTIKELBRG, CHARINDEX('.', ARTIKELBRG) + 1) - 1) ELSE LEFT(ARTIKELBRG, 7) END
            ORDER BY total_terjual DESC
        ";
        $stmt_arts = $conn->prepare($top_articles_query);
        $stmt_arts->execute($params);
        $top_articles = $stmt_arts->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $total_revenue_query = "SELECT SUM(NETTO) as total_omzet FROM V_JUAL WHERE KODESP = :kodesp AND TGL BETWEEN :start_date AND :end_date";
        $stmt_rev = $conn->prepare($total_revenue_query);
        $stmt_rev->execute($params);
        $total_revenue_result = $stmt_rev->fetch(PDO::FETCH_ASSOC);

        $analysis_results = [
            'supplier_id' => $selected_supplier_id,
            'supplier_name' => $selected_supplier_name,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'top_items' => $top_items,
            'top_categories' => $top_categories,
            'top_articles' => $top_articles,
            'total_revenue' => (float)($total_revenue_result['total_omzet'] ?? 0),
            'pagination' => [
                'page' => $page,
                'pageSize' => $limit,
                'totalPages' => $total_pages,
                'totalRecords' => $total_records,
            ],
            'filter_category' => $filter_category,
        ];
    } catch (PDOException $e) {
        json_error('Error saat melakukan analisa: ' . $e->getMessage(), 500);
    }
}

json_ok([
    'data' => [
        'suppliers' => array_map(function ($row) {
            return ['kodesp' => $row['KODESP'], 'namasp' => $row['NAMASP']];
        }, $suppliers),
        'analysis' => $analysis_results,
    ],
]);
