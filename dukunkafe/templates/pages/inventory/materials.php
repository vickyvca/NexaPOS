
<?php

require_auth(['admin', 'manager']);

$title = 'Inventory - Materials';
$pdo = get_pdo($config);

// Handle POST request for Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? null;
    $code = $_POST['code'] ?? '';
    $name = $_POST['name'] ?? '';
    $uom = $_POST['uom'] ?? '';
    $min_stock = $_POST['min_stock'] ?? 0;
    $active = isset($_POST['active']) ? 1 : 0;

    try {
        if ($action === 'create') {
            $stmt = $pdo->prepare('INSERT INTO materials (code, name, uom, min_stock, active) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$code, $name, $uom, $min_stock, 1]);
        } elseif ($action === 'update' && $id) {
            $stmt = $pdo->prepare('UPDATE materials SET code=?, name=?, uom=?, min_stock=?, active=? WHERE id=?');
            $stmt->execute([$code, $name, $uom, $min_stock, $active, $id]);
        }
        redirect(base_url('inventory/materials'));
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

// Fetch all materials
$materials = $pdo->query('SELECT * FROM materials ORDER BY name ASC')->fetchAll();

view('inventory/materials', [
    'title' => $title,
    'materials' => $materials,
]);
