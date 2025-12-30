<?php
// FILE: test_hutang.php
header('Content-Type: application/json');

require_once 'db_connection.php'; 
require_once 'HutangService.php';

// Cek koneksi
if (!isset($conn) || $conn === null) {
    die(json_encode(["status" => "fatal_error", "message" => "Koneksi DB gagal."]));
}

$hutangService = new HutangService($conn);

// --- SIMULASI INPUT PEMBAYARAN HUTANG ---

// IDAK_KAS: ID Akun Kas/Bank yang digunakan untuk membayar.
// KODESP: Kode Supplier yang memiliki hutang.
// Dapatkan IDAK_KAS dari T_REKENING yang memiliki KAS = 1 dan KODECB = '00'
// Contoh: Akun Kas dengan KODEAK '11101'
$idakKas = 1; // GANTI DENGAN IDAK KAS/BANK AKTUAL DARI TABEL T_REKENING ANDA

$headerData = [
    'KODECB' => '00',
    'KODESP' => 'SP1', // GANTI dengan Kode Supplier AKTUAL
    'TGL' => date('Y-m-d H:i:s'),
    'NILAI_KAS_BAYAR' => 1000000.00,        // Total Kas/Bank yang dikeluarkan
    'NILAI_RETUR_POTONGAN' => 50000.00,    // Potongan Retur yang mengurangi hutang
    'IDAK_KAS' => $idakKas,
    'KETERANGAN' => 'Pembayaran gabungan dari kas dan retur',
    'CUSER' => 'WEB_TEST_BHT'
];

// Detail Hutang (TVP) - Nota Hutang mana saja yang dilunasi
$detailHutang = [
    [
        // GANTI DENGAN NOTA HUTANG AKTUAL YANG ADA DI HIS_HUTANG!
        'NONOTA_HUTANG' => 'HUT0001',   
        'NILAI_BAYAR' => 500000.00      // Nilai yang akan mengurangi sisa hutang nota ini
    ],
    [
        // GANTI DENGAN NOTA HUTANG AKTUAL YANG ADA DI HIS_HUTANG!
        'NONOTA_HUTANG' => 'HUT0002',   
        'NILAI_BAYAR' => 550000.00      // Nilai yang akan mengurangi sisa hutang nota ini
    ]
    // Catatan: Total NILAI_BAYAR (1,050,000) = NILAI_KAS_BAYAR (1,000,000) + NILAI_RETUR_POTONGAN (50,000)
];
// --------------------------------------------------

// Panggil Logika Simpan Pembayaran Hutang
$result = $hutangService->prosesOtorisasiHutang($headerData, $detailHutang);

// Output Hasil
echo json_encode($result, JSON_PRETTY_PRINT);
?>