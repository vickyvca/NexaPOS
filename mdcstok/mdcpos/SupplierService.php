<?php
// FILE: SupplierService.php
require_once 'db_connection.php';

class SupplierService {
    private $conn;

    public function __construct($pdo_connection) {
        $this->conn = $pdo_connection;
    }

    /**
     * Mengambil semua data supplier dari T_SUPLIER.
     * Ini digunakan untuk dropdown pada form Pembelian dan list master.
     */
    public function getAllSuppliers() {
        try {
            // Kita hanya perlu mengambil data master supplier
            $sql = "SELECT KODESP, NAMASP, ALAMAT1, KOTA, TELP, DISCMB, JTO FROM T_SUPLIER ORDER BY NAMASP ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'status' => 'success', 
                'data' => $results,
                'count' => count($results)
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error', 
                'data' => [],
                'message' => 'Gagal mengambil data Supplier: ' . $e->getMessage()
            ];
        }
    }
}
?>