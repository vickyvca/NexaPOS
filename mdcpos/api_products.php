<?php
// FILE: api_products.php - Daftar produk untuk POS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db_connection.php';
require_once 'ProductService.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    exit;
}

if (!isset($conn) || $conn === null) {
    echo json_encode(["status" => "error", "message" => "Koneksi database tidak tersedia."]);
    exit;
}

$svc = new ProductService($conn);
$q = isset($_GET['q']) ? trim((string)$_GET['q']) : null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 200;

$result = $svc->listProducts($q ?: null, $limit);
echo json_encode($result, JSON_PRETTY_PRINT);
?>

