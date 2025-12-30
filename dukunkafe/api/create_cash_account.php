<?php

header('Content-Type: application/json');
require_once __DIR__ . '/../src/bootstrap.php';

require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

$code = $data['code'] ?? null;
$name = $data['name'] ?? null;

if (empty($code) || empty($name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Kode dan Nama Akun tidak boleh kosong.']);
    exit();
}

$pdo = get_pdo($config);

// Check for unique code
$stmt = $pdo->prepare("SELECT id FROM accounts WHERE code = ?");
$stmt->execute([$code]);
if ($stmt->fetch()) {
    http_response_code(409); // Conflict
    echo json_encode(['success' => false, 'message' => 'Kode Akun sudah ada. Harap gunakan kode yang unik.']);
    exit();
}

// Insert new account
$sql = "INSERT INTO accounts (code, name, type, active) VALUES (?, ?, 'ASSET', 1)";

try {
    $pdo = get_pdo();
    $stmt = $pdo->prepare("INSERT INTO cash_accounts (name, type) VALUES (?, ?)");
    $stmt->execute([$name, $type]);

    echo json_encode(['success' => true, 'message' => 'Akun kas berhasil ditambahkan!']);

} catch (PDOException $e) {
    // Check for duplicate entry
    if ($e->errorInfo[1] == 1062) {
        echo json_encode(['success' => false, 'message' => 'Nama akun sudah ada.']);
    } else {
        // Log error to a file for debugging, don't show to user
        error_log($e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan pada database.']);
    }
}
