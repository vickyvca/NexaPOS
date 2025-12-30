<?php
// FILE: api_master_barang.php - Endpoint untuk CREATE Master Barang (POST)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db_connection.php'; 
require_once 'BarangService.php'; // Service yang menampung logika INSERT T_BARANG, T_STOK, HIS_HPP

if (!isset($conn) || $conn === null) {
    echo json_encode(["status" => "error", "message" => "Koneksi database tidak tersedia."]);
    exit;
}

$barangService = new BarangService($conn);
$method = $_SERVER['REQUEST_METHOD'];
$result = ['status' => 'error', 'message' => 'Operasi tidak dikenal.'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Asumsi input sudah divalidasi dan lengkap dari Frontend/JavaScript
    if ($input) {
        $result = $barangService->simpanData($input);
    } else {
        $result = ['status' => 'error', 'message' => 'Data input tidak valid.'];
    }
}

if ($method === 'OPTIONS') {
    // Menangani CORS preflight request
    http_response_code(200);
    exit;
}

echo json_encode($result, JSON_PRETTY_PRINT);
?>