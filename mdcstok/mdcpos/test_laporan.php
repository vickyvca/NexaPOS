<?php
// FILE: test_laporan.php
header('Content-Type: application/json');

require_once 'db_connection.php'; 
require_once 'AccountingService.php';

// Cek koneksi
if (!isset($conn) || $conn === null) {
    die(json_encode(["status" => "fatal_error", "message" => "Koneksi DB gagal."]));
}

$accountingService = new AccountingService($conn);

// --- SIMULASI INPUT LAPORAN ---
$kodeCabang = 'ALL'; // Atau '00', '01', dll.
$bulan = 9;          // Bulan laporan
$tahun = 2025;       // Tahun laporan

// Panggil Logika Laporan
$result = $accountingService->getLabaRugi($kodeCabang, $bulan, $tahun);

// Output Hasil
echo json_encode($result, JSON_PRETTY_PRINT);
?>