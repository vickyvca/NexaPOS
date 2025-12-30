<?php
// FILE: RefMasterService.php
require_once 'db_connection.php';

class RefMasterService {
    private $conn;

    public function __construct($pdo_connection) {
        $this->conn = $pdo_connection;
    }

    // --- 1. CREATE (Simpan Data Baru) ---
    public function createJenis(array $data) {
        try {
            // Asumsi: KODEJN diinput user dan menjadi Primary Key
            $sql = "INSERT INTO REF_JENIS (KODEJN, KETERANGAN) VALUES (?, ?)";
            $stmt = $this->conn->prepare($sql);
            
            $stmt->bindParam(1, $data['KODEJN'], PDO::PARAM_STR);
            $stmt->bindParam(2, $data['KETERANGAN'], PDO::PARAM_STR);
            
            $stmt->execute();
            
            return ['status' => 'success', 'message' => 'Data Jenis baru berhasil ditambahkan.'];

        } catch (PDOException $e) {
            // Menangkap error duplikasi PK atau error DB lainnya
            return ['status' => 'error', 'message' => 'Gagal simpan data: ' . $e->getMessage()];
        }
    }

    // --- 2. READ ALL (Ambil Semua Data) ---
    public function readAllJenis() {
        try {
            $sql = "SELECT KODEJN, KETERANGAN FROM REF_JENIS ORDER BY KODEJN ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['status' => 'success', 'data' => $results];

        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Gagal mengambil data: ' . $e->getMessage()];
        }
    }

    // --- 3. UPDATE (Ubah Data) ---
    public function updateJenis(array $data) {
        try {
            $sql = "UPDATE REF_JENIS SET KETERANGAN = ? WHERE KODEJN = ?";
            $stmt = $this->conn->prepare($sql);
            
            $stmt->bindParam(1, $data['KETERANGAN'], PDO::PARAM_STR);
            $stmt->bindParam(2, $data['KODEJN'], PDO::PARAM_STR);
            
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                 return ['status' => 'info', 'message' => 'Data tidak ditemukan atau tidak ada perubahan.'];
            }
            
            return ['status' => 'success', 'message' => 'Data Jenis berhasil diperbarui.'];

        } catch (PDOException $e) {
            return ['status' => 'error', 'message' => 'Gagal update data: ' . $e->getMessage()];
        }
    }

    // --- 4. DELETE (Hapus Data) ---
    public function deleteJenis($kodeJN) {
        try {
            // Catatan: Dalam aplikasi nyata, harus ada pengecekan Foreign Key (apakah KODEJN sudah dipakai di T_BARANG)
            $sql = "DELETE FROM REF_JENIS WHERE KODEJN = ?";
            $stmt = $this->conn->prepare($sql);
            
            $stmt->bindParam(1, $kodeJN, PDO::PARAM_STR);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                 return ['status' => 'info', 'message' => 'Kode Jenis tidak ditemukan.'];
            }

            return ['status' => 'success', 'message' => 'Data Jenis berhasil dihapus.'];

        } catch (PDOException $e) {
            // Menangkap error jika KODEJN masih digunakan (Foreign Key Constraint)
            return ['status' => 'error', 'message' => 'Gagal hapus data: Data masih digunakan di tabel lain. (' . $e->getMessage() . ')'];
        }
    }
}
?>