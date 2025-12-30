<?php

require_auth(['admin', 'manager']);

$pdo = get_pdo($config);
$title = 'Manajemen Cabang';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $code = trim($_POST['code'] ?? '');
        $name = trim($_POST['name'] ?? '');
        if ($code && $name) {
            $stmt = $pdo->prepare('INSERT INTO branches (code, name, active) VALUES (?, ?, 1)');
            $stmt->execute([$code, $name]);
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $code = trim($_POST['code'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $active = isset($_POST['active']) ? 1 : 0;
        if ($id > 0 && $code && $name) {
            $stmt = $pdo->prepare('UPDATE branches SET code=?, name=?, active=? WHERE id=?');
            $stmt->execute([$code, $name, $active, $id]);
        }
    }
    redirect(base_url('branches'));
}

$branches = $pdo->query('SELECT * FROM branches ORDER BY active DESC, name ASC')->fetchAll();

view('branches', [
    'title' => $title,
    'branches' => $branches,
]);

