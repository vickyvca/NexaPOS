<?php
// FILE: api_suppliers.php - Endpoint untuk mengambil data Supplier
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Hapus ini jika sudah production

require_once 'db_connection.php'; 
require_once 'SupplierService.php';

// Cek koneksi
if (!isset($conn) || $conn === null) {
    echo json_encode(["status" => "error", "message" => "Koneksi database tidak tersedia."]);
    exit;
}

$supplierService = new SupplierService($conn);

// 1. Tentukan aksi (saat ini hanya GET)
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Aksi default: Mengambil semua data Supplier
        $result = $supplierService->getAllSuppliers();
        break;
    
    // Anda akan menambahkan aksi POST, PUT, DELETE di sini nanti

    default:
        $result = ['status' => 'error', 'message' => 'Metode request tidak didukung.'];
        break;
}

// Output hasil dalam format JSON
echo json_encode($result, JSON_PRETTY_PRINT);
?>