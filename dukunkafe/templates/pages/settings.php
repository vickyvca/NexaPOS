<?php
require_auth(['admin']);

$pdo = get_pdo();
$stmt = $pdo->query('SELECT `key`, `value` FROM settings');
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = ['cafe_name', 'cafe_address', 'cafe_logo', 'tax_rate', 'service_rate', 'inventory_mode'];
    foreach ($keys as $key) {
        if (isset($_POST[$key])) {
            $stmt = $pdo->prepare('INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?');
            $stmt->execute([$key, $_POST[$key], $_POST[$key]]);
        }
    }

    if (isset($_FILES['cafe_logo']) && $_FILES['cafe_logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../../public/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $filename = 'logo_' . time() . '_' . basename($_FILES['cafe_logo']['name']);
        $destination = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['cafe_logo']['tmp_name'], $destination)) {
            // Store web-accessible path relative to public document root
            $logo_url = '/uploads/' . $filename;
            $stmt = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES ('cafe_logo', ?) ON DUPLICATE KEY UPDATE `value` = ?");
            $stmt->execute([$logo_url, $logo_url]);
        } else {
            // Optional: implement flash messaging; for now we silently fail upload errors
        }
    }

    // Optional: implement flash messaging
    redirect(base_url('settings'));
}

$title = 'Pengaturan';
$viewPath = __DIR__ . '/settings.view.php';
require __DIR__ . '/../../templates/layout.php';
