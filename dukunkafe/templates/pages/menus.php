<?php

require_auth(['admin', 'manager']);

$pdo = get_pdo($config);

// Get settings to check for inventory mode
$settings_raw = $pdo->query("SELECT `key`, `value` FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
$settings = array_merge(['inventory_mode' => 'advanced'], $settings_raw);
$inventory_mode = $settings['inventory_mode'];

$action = $_GET['action'] ?? 'list';
$menu_id = $_GET['id'] ?? null;

switch ($action) {
    case 'new':
        $title = 'Tambah Menu Baru';
        $menu = null;
        $categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
        // Add-on groups for selection
        try { $addon_groups = $pdo->query("SELECT id, name, type, required FROM addon_groups ORDER BY name")->fetchAll(); } catch (Exception $e) { $addon_groups = []; }
        $selected_addon_group_ids = [];
        $back_url = base_url('menus');
        $viewPath = __DIR__ . '/menu_form.view.php';
        break;

    case 'edit':
        $title = 'Edit Menu';
        $stmt = $pdo->prepare("SELECT * FROM menus WHERE id = ?");
        $stmt->execute([$menu_id]);
        $menu = $stmt->fetch();
        $categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
        // Add-on groups for selection
        try { $addon_groups = $pdo->query("SELECT id, name, type, required FROM addon_groups ORDER BY name")->fetchAll(); } catch (Exception $e) { $addon_groups = []; }
        $selected_addon_group_ids = [];
        if ($menu) {
            try {
                $mg = $pdo->prepare('SELECT addon_group_id FROM menu_addon_groups WHERE menu_id = ?');
                $mg->execute([$menu['id']]);
                $selected_addon_group_ids = array_map('intval', $mg->fetchAll(PDO::FETCH_COLUMN));
            } catch (Exception $e) { $selected_addon_group_ids = []; }
        }
        $back_url = base_url('menus');
        $viewPath = __DIR__ . '/menu_form.view.php';
        break;

    case 'save':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $is_new = empty($_POST['id']);
            $fields = ['sku', 'name', 'category_id', 'price', 'is_active', 'print_station'];
            if ($inventory_mode === 'simple') {
                $fields[] = 'hpp';
            }

            // Ensure image_url column exists (ignore error if already there)
            try { $pdo->exec("ALTER TABLE menus ADD COLUMN image_url VARCHAR(255) NULL"); } catch (Exception $e) {}

            // Handle image upload (optional)
            $uploaded_image_url = '';
            if (!empty($_FILES['image_file']['tmp_name']) && ($_FILES['image_file']['error'] ?? UPLOAD_ERR_OK) === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../public/uploads/menus';
                if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0777, true); }
                $ext = pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
                $slug = preg_replace('/[^a-zA-Z0-9_-]/', '-', strtolower($_POST['sku'] ?? 'menu'));
                $filename = $slug . '-' . date('YmdHis') . ($ext ? ('.' . $ext) : '');
                if (move_uploaded_file($_FILES['image_file']['tmp_name'], $uploadDir . DIRECTORY_SEPARATOR . $filename)) {
                    $uploaded_image_url = '/uploads/menus/' . $filename;
                }
            }

            // Build SQL depending on inventory mode (avoid referencing hpp if column not used)
            if ($inventory_mode === 'simple') {
                // Ensure hpp column exists
                try { $pdo->exec("ALTER TABLE menus ADD COLUMN hpp DECIMAL(15,2) NOT NULL DEFAULT 0"); } catch (Exception $e) {}

                if ($is_new) {
                    $sql = "INSERT INTO menus (sku, name, category_id, price, is_active, print_station, hpp, image_url) VALUES (:sku, :name, :category_id, :price, :is_active, :print_station, :hpp, :image_url)";
                    $stmt = $pdo->prepare($sql);
                } else {
                    $sql = "UPDATE menus SET sku = :sku, name = :name, category_id = :category_id, price = :price, is_active = :is_active, print_station = :print_station, hpp = :hpp, image_url = COALESCE(NULLIF(:image_url, ''), image_url) WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindValue(':id', $_POST['id']);
                }

                $stmt->bindValue(':sku', $_POST['sku']);
                $stmt->bindValue(':name', $_POST['name']);
                $stmt->bindValue(':category_id', $_POST['category_id']);
                $stmt->bindValue(':price', $_POST['price']);
                $stmt->bindValue(':is_active', $_POST['is_active'] ?? 0);
                $stmt->bindValue(':print_station', $_POST['print_station']);
                $stmt->bindValue(':hpp', ($_POST['hpp'] ?? 0));
                $stmt->bindValue(':image_url', $uploaded_image_url);

            } else {
                // advanced mode: do not touch hpp column
                if ($is_new) {
                    $sql = "INSERT INTO menus (sku, name, category_id, price, is_active, print_station, image_url) VALUES (:sku, :name, :category_id, :price, :is_active, :print_station, :image_url)";
                    $stmt = $pdo->prepare($sql);
                } else {
                    $sql = "UPDATE menus SET sku = :sku, name = :name, category_id = :category_id, price = :price, is_active = :is_active, print_station = :print_station, image_url = COALESCE(NULLIF(:image_url, ''), image_url) WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindValue(':id', $_POST['id']);
                }

                $stmt->bindValue(':sku', $_POST['sku']);
                $stmt->bindValue(':name', $_POST['name']);
                $stmt->bindValue(':category_id', $_POST['category_id']);
                $stmt->bindValue(':price', $_POST['price']);
                $stmt->bindValue(':is_active', $_POST['is_active'] ?? 0);
                $stmt->bindValue(':print_station', $_POST['print_station']);
                $stmt->bindValue(':image_url', $uploaded_image_url);
            }

            $stmt->execute();

            // Save addon group mappings
            $saved_menu_id = $is_new ? (int)$pdo->lastInsertId() : (int)($_POST['id'] ?? 0);
            if ($saved_menu_id > 0) {
                $ids = array_filter(array_map('intval', $_POST['addon_group_ids'] ?? []));
                try {
                    $pdo->beginTransaction();
                    $pdo->prepare('DELETE FROM menu_addon_groups WHERE menu_id = ?')->execute([$saved_menu_id]);
                    if (!empty($ids)) {
                        $ins = $pdo->prepare('INSERT INTO menu_addon_groups (menu_id, addon_group_id) VALUES (?, ?)');
                        foreach ($ids as $gid) { $ins->execute([$saved_menu_id, $gid]); }
                    }
                    $pdo->commit();
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) { $pdo->rollBack(); }
                    // ignore if addons schema not present
                }
            }
        }
        redirect(base_url('menus'));
        break;

    case 'delete':
        if ($menu_id) {
            $stmt = $pdo->prepare("DELETE FROM menus WHERE id = ?");
            $stmt->execute([$menu_id]);
        }
        redirect(base_url('menus'));
        break;

    default: // list
        $title = 'Manajemen Menu';
        $menus = $pdo->query("SELECT m.*, c.name as category_name FROM menus m LEFT JOIN categories c ON m.category_id = c.id ORDER BY m.name ASC")->fetchAll();
        $viewPath = __DIR__ . '/menus.view.php';
        break;
}

require __DIR__ . '/../layout.php';
