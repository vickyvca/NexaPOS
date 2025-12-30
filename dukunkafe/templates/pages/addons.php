<?php

require_auth(['admin', 'manager']);

$pdo = get_pdo($config);

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($action) {
    case 'new':
        $title = 'Tambah Add-on';
        $addon = null;
        try { $groups = $pdo->query('SELECT id, name FROM addon_groups ORDER BY name')->fetchAll(); } catch (Exception $e) { $groups = []; }
        $back_url = base_url('addons');
        $viewPath = __DIR__ . '/addon_form.view.php';
        break;

    case 'edit':
        $title = 'Edit Add-on';
        $stmt = $pdo->prepare('SELECT * FROM addons WHERE id = ?');
        try { $stmt->execute([$id]); } catch (Exception $e) { $stmt = null; }
        $addon = $stmt ? $stmt->fetch() : null;
        try { $groups = $pdo->query('SELECT id, name FROM addon_groups ORDER BY name')->fetchAll(); } catch (Exception $e) { $groups = []; }
        $back_url = base_url('addons');
        $viewPath = __DIR__ . '/addon_form.view.php';
        break;

    case 'save':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $is_new = empty($_POST['id']);
            $name = trim($_POST['name'] ?? '');
            $group_id = (int)($_POST['addon_group_id'] ?? 0);
            $price = (float)($_POST['price'] ?? 0);
            if ($name === '' || $group_id <= 0) { redirect(base_url('addons')); }
            try {
                if ($is_new) {
                    $stmt = $pdo->prepare('INSERT INTO addons (addon_group_id, name, price) VALUES (?, ?, ?)');
                    $stmt->execute([$group_id, $name, $price]);
                } else {
                    $stmt = $pdo->prepare('UPDATE addons SET addon_group_id = ?, name = ?, price = ? WHERE id = ?');
                    $stmt->execute([$group_id, $name, $price, (int)$_POST['id']]);
                }
            } catch (Exception $e) {}
        }
        redirect(base_url('addons'));
        break;

    case 'delete':
        if ($id) {
            try {
                $stmt = $pdo->prepare('DELETE FROM addons WHERE id = ?');
                $stmt->execute([$id]);
            } catch (Exception $e) {
                // ignore; might be referenced by order history
            }
        }
        redirect(base_url('addons'));
        break;

    default:
        $title = 'Add-ons';
        try {
            $addons = $pdo->query('SELECT a.*, g.name as group_name FROM addons a JOIN addon_groups g ON a.addon_group_id = g.id ORDER BY g.name, a.name')->fetchAll();
        } catch (Exception $e) {
            $addons = [];
        }
        $viewPath = __DIR__ . '/addons.view.php';
        break;
}

require __DIR__ . '/../layout.php';

