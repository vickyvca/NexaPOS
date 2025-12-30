<?php

require_auth(['admin','manager']);

$pdo = get_pdo($config);
$title = 'Pengaturan Per Cabang';

// Load branches
$branches = $pdo->query("SELECT id, name FROM branches WHERE active=1 ORDER BY name")->fetchAll();
$branch_id = (int)($_GET['branch_id'] ?? ($_POST['branch_id'] ?? (get_current_branch_id())));
if (!$branch_id && !empty($branches)) { $branch_id = (int)$branches[0]['id']; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pairs = [
        'cafe_name' => trim($_POST['cafe_name'] ?? ''),
        'cafe_address' => trim($_POST['cafe_address'] ?? ''),
    ];
    foreach ($pairs as $k => $v) {
        $key = 'branch:' . $branch_id . ':' . $k;
        $stmt = $pdo->prepare("INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
        $stmt->execute([$key, $v]);
    }

    if (isset($_FILES['cafe_logo']) && $_FILES['cafe_logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../../public/uploads/';
        if (!is_dir($upload_dir)) { @mkdir($upload_dir, 0777, true); }
        $filename = 'logo_branch' . $branch_id . '_' . time() . '_' . basename($_FILES['cafe_logo']['name']);
        $destination = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['cafe_logo']['tmp_name'], $destination)) {
            $logo_url = '/uploads/' . $filename;
            $key = 'branch:' . $branch_id . ':cafe_logo';
            $stmt = $pdo->prepare("INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
            $stmt->execute([$key, $logo_url]);
        }
    }

    redirect(base_url('branch_settings&branch_id=' . $branch_id));
}

// Load effective settings (with overrides applied) for the selected branch
$settings = load_settings($pdo);

view('branch_settings', [
    'title' => $title,
    'branches' => $branches,
    'branch_id' => $branch_id,
    'settings' => $settings,
]);

