<?php
// Endpoint API Simulasi untuk menyimpan Master Barang
header('Content-Type: application/json');

require_once 'BarangService.php';
require_once 'db_connection.php'; 

$barangService = new BarangService($conn);

// --- SIMULASI INPUT DATA ---
// Data yang dikirimkan harus mencakup semua kolom yang TIDAK boleh NULL (NO) di skema Anda.
// KODECB (Cabang), KODEJN, KODEKT, KODEMR, KODEST diambil dari tabel referensi.
$randomCode = rand(100, 999);

$inputData = [
    'KODEBRG' => 'PRD-' . $randomCode,
    'NAMABRG' => 'Web Konversi Tes ' . $randomCode,
    'KODESP' => 'SP1', 
    'KODEJN' => '01', 
    'KODEMR' => 'MR1', 
    'KODEST' => 'ST1',
    'HGBELI' => 50000.00,
    'HGJUAL' => 75000.00,
    'DISC' => 0.05,
    'MARKUP' => 0.3,
    'TGLBELI' => date('Y-m-d H:i:s'),
    'CUSER' => 'WEB_TEST',
    'CKOMP' => 'PHP_APP',
    
    // Stok (T_STOK)
    'STOKAWAL' => 100,
    'STOKMIN' => 10,
    'STOKMAX' => 500,
    'ST00' => 50, // Stok Pusat
    'ST01' => 10, // Stok Cabang 01
    'ST02' => 20,
    'ST03' => 10,
    'ST04' => 10
];
// ----------------------------

// Panggil Logika Simpan
$result = $barangService->simpanData($inputData);

// Output Hasil
echo json_encode($result);
?>