<?php
// FILE: test_jual.php (Jalankan file ini untuk menguji)
header('Content-Type: application/json');

require_once 'db_connection.php'; 
require_once 'VBSalesService.php';

// Cek koneksi
if (!isset($conn) || $conn === null) {
    // Fungsi die() sudah dipanggil di db_connection, jadi ini hanya safety check
    die(json_encode(["status" => "fatal_error", "message" => "Koneksi DB gagal."]));
}

$salesService = new VBSalesService($conn);

// --- SIMULASI DATA LENGKAP DARI FORM ---
$headerData = [
    'KODECB' => '00',       // Harus ada di T_CABANG
    'KODESL' => 'SL1',       // Harus ada di T_SALES
    'KODEMB' => 'MB0001',    // Harus ada di T_MEMBER
    'CARABAYAR' => 1,       
    'STATUS' => 1,          
    'BULAT' => 0.00,
    'RETUR' => 0.00,
    'BAYAR' => 210000.00,  
    'GROSIR' => 0,
    'REFCARD' => '',
    'POTADMIN' => 0.00,
    'CUSER' => 'WEB_TEST'
];

// Data Detail (HIS_DTJUAL) - Pastikan ID ada di T_BARANG
$detailItems = [
    [
        'ID' => 1, // ID Barang
        'QTY' => 2,
        'HGJUAL' => 80000.00,
        'HGBELI' => 50000.00,
        'BRUTO' => 160000.00,
        'DISC1' => 0.00,
        'DISC2' => 0.00,
        'HITDISC1' => 0.00,
        'HITDISC2' => 0.00,
        'NETTO' => 160000.00
    ],
    [
        'ID' => 2, // ID Barang
        'QTY' => 1,
        'HGJUAL' => 50000.00,
        'HGBELI' => 20000.00,
        'BRUTO' => 50000.00,
        'DISC1' => 0.00,
        'DISC2' => 0.00,
        'HITDISC1' => 0.00,
        'HITDISC2' => 0.00,
        'NETTO' => 50000.00
    ]
];
// --------------------------------------------------

// Panggil Logika Simpan
$result = $salesService->simpanTransaksiJual($headerData, $detailItems);

// Output Hasil
echo json_encode($result);
?>