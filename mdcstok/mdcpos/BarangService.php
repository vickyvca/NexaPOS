<?php
// Pastikan file koneksi sudah di-require
require_once 'db_connection.php';

class BarangService {
    private $conn;

    public function __construct($pdo_connection) {
        $this->conn = $pdo_connection;
    }

    /**
     * Mereplikasi simpanData() dari frmInputBarang: INSERT ke T_BARANG, T_STOK, dan HIS_HPP.
     */
    public function simpanData(array $data) {
        try {
            // Memulai Transaksi: Jika satu query gagal, semua dibatalkan
            $this->conn->beginTransaction();

            // Mendapatkan ID Kunci Utama (Diasumsikan ID adalah Auto-Increment/Sequence)
            $id = $this->getLastId('T_BARANG') + 1; 
            $idhis = $this->getLastId('HIS_HPP', 'IDHIS') + 1; // IDHIS untuk History HPP

            // 1. INSERT ke T_BARANG (Data Master)
            $sqlBarang = "INSERT INTO T_BARANG (ID, KODEBRG, NAMABRG, KODESP, KODEJN, KODEMR, KODEST, HGBELI, HGJUAL, DISC, MARKUP, TGLBELI, CUSER, CKOMP, CDATE)
                          VALUES (:id, :kodebrg, :namabrg, :kodesp, :kodejn, :kodemr, :kodest, :hgbeli, :hgjual, :disc, :markup, :tglbeli, :cuser, :ckomp, GETDATE())";
            $stmtBarang = $this->conn->prepare($sqlBarang);
            $stmtBarang->execute([
                ':id' => $id,
                ':kodebrg' => $data['KODEBRG'],
                ':namabrg' => $data['NAMABRG'],
                ':kodesp' => $data['KODESP'],
                ':kodejn' => $data['KODEJN'],
                ':kodemr' => $data['KODEMR'],
                ':kodest' => $data['KODEST'],
                ':hgbeli' => $data['HGBELI'],
                ':hgjual' => $data['HGJUAL'],
                ':disc' => $data['DISC'],
                ':markup' => $data['MARKUP'],
                ':tglbeli' => $data['TGLBELI'],
                ':cuser' => $data['CUSER'],
                ':ckomp' => $data['CKOMP']
            ]);

            // 2. INSERT ke T_STOK (Stok Awal)
            $sqlStok = "INSERT INTO T_STOK (ID, STOKAWAL, STOKMIN, STOKMAX, ST00, ST01, ST02, ST03, ST04)
                        VALUES (:id, :stokawal, :stokmin, :stokmax, :stok00, :stok01, :stok02, :stok03, :stok04)";
            $stmtStok = $this->conn->prepare($sqlStok);
            $stmtStok->execute([
                ':id' => $id,
                ':stokawal' => $data['STOKAWAL'],
                ':stokmin' => $data['STOKMIN'],
                ':stokmax' => $data['STOKMAX'],
                ':stok00' => $data['ST00'], 
                ':stok01' => $data['ST01'],
                ':stok02' => $data['ST02'],
                ':stok03' => $data['ST03'],
                ':stok04' => $data['ST04']
            ]);

            // 3. INSERT ke HIS_HPP (Log HPP Awal)
            $sqlHPP = "INSERT INTO HIS_HPP (IDHIS, ID, HGLAMA, HGBARU, JENIS, CUSER, CKOMP, CDATE)
                       VALUES (:idhis, :id, :hglama, :hgbaru, :jenis, :cuser, :ckomp, GETDATE())";
            $stmtHPP = $this->conn->prepare($sqlHPP);
            $stmtHPP->execute([
                ':idhis' => $idhis,
                ':id' => $id,
                ':hglama' => 0, 
                ':hgbaru' => $data['HGBELI'],
                ':jenis' => 1, // Jenis 1: Asumsi Item Baru
                ':cuser' => $data['CUSER'],
                ':ckomp' => $data['CKOMP']
            ]);

            $this->conn->commit();
            return ['status' => 'success', 'message' => 'Data Barang baru berhasil disimpan (ID: ' . $id . ')'];

        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['status' => 'error', 'message' => 'Gagal menyimpan data: ' . $e->getMessage()];
        }
    }

    /**
     * Fungsi helper untuk mensimulasikan pencarian ID terbesar (MAX ID) 
     * karena PHP tidak bisa tahu sequence/identity SQL Server secara pasif.
     */
    private function getLastId($tableName, $idColumnName = 'ID') {
        $sql = "SELECT MAX($idColumnName) FROM " . $tableName;
        
        // Asumsi HIS_HPP menggunakan kolom IDHIS
        if ($tableName === 'HIS_HPP') {
            $sql = "SELECT MAX(IDHIS) FROM HIS_HPP";
        }

        $stmt = $this->conn->query($sql);
        $lastId = $stmt->fetchColumn();
        return $lastId ? (int)$lastId : 0;
    }
}
?>