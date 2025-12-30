
<?php

require_auth(['admin', 'manager']);

$title = 'Inventory - Stock Cards';
$pdo = get_pdo($config);

// Get search term
$search = $_GET['search'] ?? '';

$sql = "
    SELECT 
        m.id,
        m.name,
        m.code,
        m.uom,
        m.min_stock,
        COALESCE(sc.qty_on_hand, 0) AS qty_on_hand
    FROM materials m
    LEFT JOIN stock_cards sc ON m.id = sc.material_id
";

$params = [];
if (!empty($search)) {
    $sql .= " WHERE m.name LIKE ? OR m.code LIKE ?";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$sql .= " ORDER BY m.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$stock_levels = $stmt->fetchAll();

view('inventory/stock', [
    'title' => $title,
    'stock_levels' => $stock_levels,
    'search' => $search,
]);
