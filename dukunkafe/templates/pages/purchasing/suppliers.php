
<?php

require_auth(['admin', 'manager']);

$title = 'Purchasing - Suppliers';
$pdo = get_pdo($config);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? null;
    $code = $_POST['code'] ?? '';
    $name = $_POST['name'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';

    try {
        if ($action === 'create') {
            $stmt = $pdo->prepare('INSERT INTO suppliers (code, name, contact, phone, address) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$code, $name, $contact, $phone, $address]);
        } elseif ($action === 'update' && $id) {
            $stmt = $pdo->prepare('UPDATE suppliers SET code=?, name=?, contact=?, phone=?, address=? WHERE id=?');
            $stmt->execute([$code, $name, $contact, $phone, $address, $id]);
        }
        redirect(base_url('purchasing/suppliers'));
    } catch (PDOException $e) {
        die("Database error: " . $e->getMessage());
    }
}

$suppliers = $pdo->query('SELECT * FROM suppliers ORDER BY name ASC')->fetchAll();

view('purchasing/suppliers', [
    'title' => $title,
    'suppliers' => $suppliers,
]);
