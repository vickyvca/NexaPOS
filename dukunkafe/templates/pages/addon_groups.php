<?php

require_auth(['admin', 'manager']);

$pdo = get_pdo($config);

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

switch ($action) {
    case 'new':
        $title = 'Tambah Add-on Group';
        $group = null;
        $back_url = base_url('addon_groups');
        $viewPath = __DIR__ . '/addon_group_form.view.php';
        break;

    case 'edit':
        $title = 'Edit Add-on Group';
        $stmt = $pdo->prepare('SELECT * FROM addon_groups WHERE id = ?');
        try { $stmt->execute([$id]); } catch (Exception $e) { $stmt = null; }
        $group = $stmt ? $stmt->fetch() : null;
        $back_url = base_url('addon_groups');
        $viewPath = __DIR__ . '/addon_group_form.view.php';
        break;

    case 'save':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $is_new = empty($_POST['id']);
            $name = trim($_POST['name'] ?? '');
            $type = $_POST['type'] ?? 'checkbox';
            $required = !empty($_POST['required']) ? 1 : 0;

            if ($name === '') { redirect(base_url('addon_groups')); }

            try {
                if ($is_new) {
                    $stmt = $pdo->prepare('INSERT INTO addon_groups (name, type, required) VALUES (?, ?, ?)');
                    $stmt->execute([$name, $type, $required]);
                } else {
                    $stmt = $pdo->prepare('UPDATE addon_groups SET name = ?, type = ?, required = ? WHERE id = ?');
                    $stmt->execute([$name, $type, $required, (int)$_POST['id']]);
                }
            } catch (Exception $e) {
                // ignore for now
            }
        }
        redirect(base_url('addon_groups'));
        break;

    case 'delete':
        if ($id) {
            try {
                $stmt = $pdo->prepare('DELETE FROM addon_groups WHERE id = ?');
                $stmt->execute([$id]);
            } catch (Exception $e) {
                // ignore; might be referenced
            }
        }
        redirect(base_url('addon_groups'));
        break;

    default:
        $title = 'Add-on Groups';
        try {
            $groups = $pdo->query('SELECT * FROM addon_groups ORDER BY name')->fetchAll();
        } catch (Exception $e) {
            $groups = [];
        }
        $viewPath = __DIR__ . '/addon_groups.view.php';
        break;
}

require __DIR__ . '/../layout.php';

