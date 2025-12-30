<?php
// FILE: api_pos_sale.php - Simpan transaksi jual (POS checkout)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db_connection.php';
require_once 'VBSalesService.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['status' => 'ok']);
    exit;
}

if (!isset($conn) || $conn === null) {
    echo json_encode(["status" => "error", "message" => "Koneksi database tidak tersedia."]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'Payload tidak valid.']);
    exit;
}

// Header defaults (sesuaikan dengan kebutuhan Anda/DB)
$header = [
    'KODECB' => $input['KODECB'] ?? '00',
    'KODESL' => $input['KODESL'] ?? 'SL1',
    'KODEMB' => $input['KODEMB'] ?? 'MB0001',
    'CARABAYAR' => (int)($input['CARABAYAR'] ?? 1),
    'STATUS' => (int)($input['STATUS'] ?? 1),
    'BULAT' => (float)($input['BULAT'] ?? 0),
    'RETUR' => (float)($input['RETUR'] ?? 0),
    'BAYAR' => (float)($input['BAYAR'] ?? 0),
    'GROSIR' => (int)($input['GROSIR'] ?? 0),
    'REFCARD' => $input['REFCARD'] ?? '',
    'POTADMIN' => (float)($input['POTADMIN'] ?? 0),
    'CUSER' => $input['CUSER'] ?? 'WEB_POS',
];

$items = $input['ITEMS'] ?? [];
if (!is_array($items) || count($items) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'ITEMS tidak boleh kosong.']);
    exit;
}

// Hitung BAYAR dari items jika belum ada
if (empty($input['BAYAR'])) {
    $total = 0.0;
    foreach ($items as $it) {
        $qty = (float)($it['QTY'] ?? 0);
        $price = (float)($it['HGJUAL'] ?? 0);
        $total += $qty * $price;
    }
    $header['BAYAR'] = $total;
}

$svc = new VBSalesService($conn);
$result = $svc->simpanTransaksiJual($header, $items);
echo json_encode($result, JSON_PRETTY_PRINT);
?>

