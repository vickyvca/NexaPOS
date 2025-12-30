<?php
// FILE: AccountingService.php
require_once 'db_connection.php';

class AccountingService {
    private $conn;

    public function __construct($pdo_connection) {
        $this->conn = $pdo_connection;
    }

    /**
     * Mengeksekusi SP LAP_LABARUGI dan mengambil hasilnya.
     * SP ini menggunakan temporary tables (#TEMP) sehingga harus dijalankan sebagai single query.
     */
    public function getLabaRugi($kodeCB, $bulan, $tahun) {
        try {
            // 1. Definisikan SQL untuk memanggil SP
            $sql = "EXEC [dbo].[LAP_LABARUGI] @KODECB = ?, @BULAN = ?, @TAHUN = ?";
            
            $stmt = $this->conn->prepare($sql);
            
            // 2. Binding Parameters
            // Pastikan tipe data INT/STRING sesuai dengan yang diharapkan SP
            $stmt->bindParam(1, $kodeCB, PDO::PARAM_STR); 
            $stmt->bindParam(2, $bulan, PDO::PARAM_INT);
            $stmt->bindParam(3, $tahun, PDO::PARAM_INT);
            
            // 3. Eksekusi
            $stmt->execute();

            // 4. Ambil semua hasil
            // SP Laporan mengembalikan hasil, jadi kita ambil semua barisnya
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'status' => 'success', 
                'data' => $results,
                'message' => 'Laporan Laba Rugi berhasil diambil.'
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error', 
                'data' => [],
                'message' => 'Gagal mengambil laporan: ' . $e->getMessage()
            ];
        }
    }

    // Fungsi untuk LAP_NERACA akan mirip dengan ini...
}
?>