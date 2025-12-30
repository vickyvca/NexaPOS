<?php
// FILE: db_connection.php

// Aktifkan error display (untuk debugging di lingkungan development)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// *** Konfigurasi via environment variable (fallback ke default) ***
$serverAddress = getenv('DB_HOST') ?: '192.168.4.99';
$port = getenv('DB_PORT') ?: '1433'; // Port sebagai string

$dbName = getenv('DB_NAME') ?: 'MODECENTRE2';
$user = getenv('DB_USER') ?: 'sa';
$password = getenv('DB_PASSWORD') ?: 'mode1234ABC';

// String DSN yang benar: Server=alamat,port
$dsn = "sqlsrv:Server={$serverAddress},{$port};Database={$dbName}";

try {
    // Membuat koneksi PDO
    $conn = new PDO($dsn, $user, $password, [
        // Mengatur Error Mode ke Exception agar kita bisa menangkap error SQL
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        // Mengatur encoding (optional, tapi disarankan untuk karakter Indonesia)
        PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8 
    ]);
    
    // echo "Koneksi database berhasil!"; // Hapus atau jadikan komentar setelah pengujian sukses

} catch (PDOException $e) {
    // Tangani kegagalan koneksi dan tampilkan pesan error
    die("<h3 style='color:red'>Koneksi database GAGAL:</h3>" 
        . "<pre>Pastikan driver PDO SQLSRV terinstal dan server aktif (192.168.4.99:1433).</pre>"
        . "<p>Detail Error: " . $e->getMessage() . "</p>"
    );
}
?>
