<?php

require_auth(['admin', 'manager']);

$pdo = get_pdo($config);
$menu_id = $_GET['id'] ?? null;

if (!$menu_id) {
    redirect(base_url('inventory_menus'));
}

// Handle Deletion
if (($_GET['action'] ?? '') === 'delete' && isset($_GET['bom_id'])) {
    $delete_stmt = $pdo->prepare('DELETE FROM menu_bom WHERE id = ? AND menu_id = ?');
    $delete_stmt->execute([$_GET['bom_id'], $menu_id]);
    redirect(base_url('menu_recipe?id=' . $menu_id));
}

// Handle Addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_ingredient'])) {
    $material_id = $_POST['material_id'];
    $qty = $_POST['qty'];
    // UOM is fetched from material table for consistency
    $material_stmt = $pdo->prepare('SELECT uom FROM materials WHERE id = ?');
    $material_stmt->execute([$material_id]);
    $uom = $material_stmt->fetchColumn();

    if ($material_id && $qty && $uom) {
        $insert_stmt = $pdo->prepare('INSERT INTO menu_bom (menu_id, material_id, qty, uom) VALUES (?, ?, ?, ?)');
        $insert_stmt->execute([$menu_id, $material_id, $qty, $uom]);
    }
    redirect(base_url('menu_recipe?id=' . $menu_id));
}

// Fetch Data for View
$menu_stmt = $pdo->prepare('SELECT * FROM menus WHERE id = ?');
$menu_stmt->execute([$menu_id]);
$menu = $menu_stmt->fetch();

if (!$menu) {
    redirect(base_url('inventory_menus'));
}

$recipe_stmt = $pdo->prepare("
    SELECT mb.id, m.name, m.uom, mb.qty 
    FROM menu_bom mb 
    JOIN materials m ON mb.material_id = m.id 
    WHERE mb.menu_id = ? 
    ORDER BY m.name ASC
");
$recipe_stmt->execute([$menu_id]);
$recipe_items = $recipe_stmt->fetchAll();

$materials = $pdo->query('SELECT id, name, uom FROM materials ORDER BY name ASC')->fetchAll();

$title = 'Kelola Resep: ' . htmlspecialchars($menu['name']);
$back_url = base_url('inventory_menus'); // Custom back URL
$viewPath = __DIR__ . '/menu_recipe.view.php';
require __DIR__ . '/../layout.php';
