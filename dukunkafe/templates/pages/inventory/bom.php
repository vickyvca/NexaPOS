
<?php

require_auth(['admin', 'manager']);

$title = 'Inventory - Bill of Materials';
$pdo = get_pdo($config);

// Get selected menu, default to the first one if not set
$menu_id = $_GET['menu_id'] ?? null;

// Handle POST requests to update BOM
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_menu_id = $_POST['menu_id'] ?? null;
    $action = $_POST['action'] ?? '';

    if ($post_menu_id) {
        if ($action === 'add_material') {
            $material_id = $_POST['material_id'] ?? null;
            $qty = $_POST['qty'] ?? 0;
            $uom = $_POST['uom'] ?? '';
            if ($material_id && $qty > 0) {
                $stmt = $pdo->prepare('INSERT INTO menu_bom (menu_id, material_id, qty, uom) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE qty=?, uom=?');
                $stmt->execute([$post_menu_id, $material_id, $qty, $uom, $qty, $uom]);
            }
        } elseif ($action === 'delete_material') {
            $bom_id = $_POST['bom_id'] ?? null;
            if ($bom_id) {
                $stmt = $pdo->prepare('DELETE FROM menu_bom WHERE id = ? AND menu_id = ?');
                $stmt->execute([$bom_id, $post_menu_id]);
            }
        }
    }
    // Redirect to the same page to show changes
    redirect(base_url('inventory/bom?menu_id=' . $post_menu_id));
}

// Fetch all menus for the dropdown
$menus = $pdo->query('SELECT id, name FROM menus WHERE is_active = 1 ORDER BY name ASC')->fetchAll();

// Fetch all materials for the add form
$materials = $pdo->query('SELECT id, name, uom FROM materials WHERE active = 1 ORDER BY name ASC')->fetchAll();

$bom_items = [];
if ($menu_id) {
    $stmt = $pdo->prepare("
        SELECT mb.id, m.name, mb.qty, mb.uom
        FROM menu_bom mb
        JOIN materials m ON mb.material_id = m.id
        WHERE mb.menu_id = ?
        ORDER BY m.name
    ");
    $stmt->execute([$menu_id]);
    $bom_items = $stmt->fetchAll();
} elseif (!empty($menus)) {
    // If no menu is selected, default to the first menu
    $menu_id = $menus[0]['id'];
    redirect(base_url('inventory/bom?menu_id=' . $menu_id));
}

view('inventory/bom', [
    'title' => $title,
    'menus' => $menus,
    'materials' => $materials,
    'bom_items' => $bom_items,
    'selected_menu_id' => $menu_id,
]);
