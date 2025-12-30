<?php
// FILE: ProductService.php
require_once 'db_connection.php';

class ProductService {
    private $conn;

    public function __construct($pdo_connection) {
        $this->conn = $pdo_connection;
    }

    // Simple listing with optional search and limit
    public function listProducts(?string $q = null, int $limit = 200) {
        try {
            $limit = max(1, min($limit, 500));
            if ($q) {
                $sql = "SELECT TOP {$limit} ID, KODEBRG, NAMABRG, HGJUAL FROM T_BARANG WHERE NAMABRG LIKE ? OR KODEBRG LIKE ? ORDER BY NAMABRG ASC";
                $stmt = $this->conn->prepare($sql);
                $like = '%' . $q . '%';
                $stmt->execute([$like, $like]);
            } else {
                $sql = "SELECT TOP {$limit} ID, KODEBRG, NAMABRG, HGJUAL FROM T_BARANG ORDER BY NAMABRG ASC";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute();
            }
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return ['status' => 'success', 'data' => $rows, 'count' => count($rows)];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Gagal mengambil produk: ' . $e->getMessage()];
        }
    }
}
?>

