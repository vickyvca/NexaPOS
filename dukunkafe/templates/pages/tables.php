<?php

require_auth(['admin', 'kasir', 'waiter', 'manager']);

$pdo = get_pdo($config);
$title = 'Table Layout';

// CRUD for tables (admin/manager only)
if (in_array(get_user_role(), ['admin','manager'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        if ($action === 'create') {
            $code = trim($_POST['code'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $area = trim($_POST['area'] ?? '');
            $capacity = (int)($_POST['capacity'] ?? 0);
            $branch_id = (int)($_POST['branch_id'] ?? get_current_branch_id());
            if ($code && $name && $capacity > 0) {
                $stmt = $pdo->prepare('INSERT INTO tables (code, name, area, capacity, status, branch_id, created_at) VALUES (?,?,?,?,\'AVAILABLE\',?, NOW())');
                $stmt->execute([$code, $name, $area, $capacity, $branch_id]);
            }
        } elseif ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $code = trim($_POST['code'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $area = trim($_POST['area'] ?? '');
            $capacity = (int)($_POST['capacity'] ?? 0);
            if ($id > 0 && $code && $name && $capacity > 0) {
                $stmt = $pdo->prepare('UPDATE tables SET code=?, name=?, area=?, capacity=? WHERE id=?');
                $stmt->execute([$code, $name, $area, $capacity, $id]);
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $pdo->prepare('DELETE FROM tables WHERE id=?')->execute([$id]);
            }
        }
        redirect(base_url('tables'));
    }
}

// Data for management block
$branch_id = get_current_branch_id();
$branches = [];
try { $branches = $pdo->query("SELECT id, name FROM branches WHERE active=1 ORDER BY name")->fetchAll(); } catch (Exception $e) {}
$tables_admin = $pdo->prepare('SELECT * FROM tables WHERE branch_id = ? ORDER BY name');
$tables_admin->execute([$branch_id]);
$tables_list = $tables_admin->fetchAll();

view('tables', [
    'title' => $title,
    'branches' => $branches,
    'tables_list' => $tables_list,
    'branch_id' => $branch_id,
]);