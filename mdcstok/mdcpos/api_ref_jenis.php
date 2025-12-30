<?php
// FILE: api_ref_jenis.php - Universal Endpoint CRUD untuk REF_JENIS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db_connection.php'; 
require_once 'RefMasterService.php';

if (!isset($conn) || $conn === null) {
    echo json_encode(["status" => "error", "message" => "Koneksi database tidak tersedia."]);
    exit;
}

$refService = new RefMasterService($conn);
$method = $_SERVER['REQUEST_METHOD'];
$result = ['status' => 'error', 'message' => 'Operasi tidak dikenal.'];

// Ambil data input mentah (dari POST, PUT, DELETE requests)
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'GET':
        // Cek jika ada query parameter untuk READ ONE (misalnya ?kode=...)
        $kode = $_GET['kode'] ?? null;
        
        if ($kode) {
            // (TODO: Tambahkan fungsi readOneJenis($kode) di service)
            $result = ['status' => 'info', 'message' => 'Fungsi READ ONE belum diimplementasikan.'];
        } else {
            // READ ALL
            $result = $refService->readAllJenis();
        }
        break;

    case 'POST':
        // CREATE (Data dari body request JSON)
        if (isset($input['KODEJN']) && isset($input['KETERANGAN'])) {
            $result = $refService->createJenis($input);
        } else {
            $result = ['status' => 'error', 'message' => 'Data KODEJN dan KETERANGAN wajib diisi.'];
        }
        break;

    case 'PUT':
        // UPDATE (Data dari body request JSON)
        if (isset($input['KODEJN']) && isset($input['KETERANGAN'])) {
            $result = $refService->updateJenis($input);
        } else {
            $result = ['status' => 'error', 'message' => 'Data KODEJN dan KETERANGAN wajib diisi.'];
        }
        break;

    case 'DELETE':
        // DELETE (Ambil KODEJN dari body request atau query string)
        $kodeToDelete = $input['KODEJN'] ?? null;
        if ($kodeToDelete) {
            $result = $refService->deleteJenis($kodeToDelete);
        } else {
            $result = ['status' => 'error', 'message' => 'KODEJN untuk dihapus wajib diisi.'];
        }
        break;
        
    case 'OPTIONS':
        // Menangani CORS preflight request
        http_response_code(200);
        exit;
}

// Output hasil
echo json_encode($result, JSON_PRETTY_PRINT);
?>