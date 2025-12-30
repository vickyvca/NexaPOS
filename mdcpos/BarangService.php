<?php
// FILE: BarangService.php
require_once 'db_connection.php';

class BarangService {
    private $conn;

    public function __construct($pdo_connection) {
        $this->conn = $pdo_connection;
    }

    private function getLastId($tableName, $idColumnName = 'ID') {
        $sql = ($tableName === 'HIS_HPP') ? "SELECT MAX(IDHIS) FROM HIS_HPP" : "SELECT MAX(ID) FROM " . $tableName;
        $stmt = $this->conn->query($sql);
        $lastId = $stmt->fetchColumn();
        return $lastId ? (int)$lastId : 0;
    }

    // --- CREATE (Replikasi simpanData) ---
    public function simpanData(array $data) {
        try {
            $this->conn->beginTransaction();

            $id = $this->getLastId('T_BARANG') + 1; 
            $idhis = $this->getLastId('HIS_HPP', 'IDHIS') + 1;

            // 1. INSERT ke T_BARANG
            $sqlBarang = "INSERT INTO T_BARANG (ID, KODEBRG, NAMABRG, KODESP, KODEJN, KODEMR, KODEST, HGBELI, HGJUAL, DISC, MARKUP, TGLBELI, CUSER, CKOMP, CDATE)
                          VALUES (:id, :kodebrg, :namabrg, :kodesp, :kodejn, :kodemr, :kodest, :hgbeli, :hgjual, :disc, :markup, :tglbeli, :cuser, :ckomp, GETDATE())";
            $stmtBarang = $this->conn->prepare($sqlBarang);
            $stmtBarang->execute([
                ':id' => $id, ':kodebrg' => $data['KODEBRG'], ':namabrg' => $data['NAMABRG'], ':kodesp' => $data['KODESP'], 
                ':kodejn' => $data['KODEJN'], ':kodemr' => $data['KODEMR'], ':kodest' => $data['KODEST'], ':hgbeli' => $data['HGBELI'], 
                ':hgjual' => $data['HGJUAL'], ':disc' => $data['DISC'], ':markup' => $data['MARKUP'], ':tglbeli' => $data['TGLBELI'], 
                ':cuser' => $data['CUSER'], ':ckomp' => $data['CKOMP']
            ]);

            // 2. INSERT ke T_STOK
            $sqlStok = "INSERT INTO T_STOK (ID, STOKAWAL, STOKMIN, STOKMAX, ST00, ST01, ST02, ST03, ST04)
                        VALUES (:id, :stokawal, :stokmin, :stokmax, :stok00, :stok01, :stok02, :stok03, :stok04)";
            $stmtStok = $this->conn->prepare($sqlStok);
            $stmtStok->execute([
                ':id' => $id, ':stokawal' => $data['STOKAWAL'], ':stokmin' => $data['STOKMIN'], 
                ':stokmax' => $data['STOKMAX'], ':stok00' => $data['ST00'], ':stok01' => $data['ST01'], 
                ':stok02' => $data['ST02'], ':stok03' => $data['ST03'], ':stok04' => $data['ST04']
            ]);

            // 3. INSERT ke HIS_HPP
            $sqlHPP = "INSERT INTO HIS_HPP (IDHIS, ID, HGLAMA, HGBARU, JENIS, CUSER, CKOMP, CDATE)
                       VALUES (:idhis, :id, :hglama, :hgbaru, :jenis, :cuser, :ckomp, GETDATE())";
            $stmtHPP = $this->conn->prepare($sqlHPP);
            $stmtHPP->execute([
                ':idhis' => $idhis, ':id' => $id, ':hglama' => 0, ':hgbaru' => $data['HGBELI'], 
                ':jenis' => 1, ':cuser' => $data['CUSER'], ':ckomp' => $data['CKOMP']
            ]);

            $this->conn->commit();
            return ['status' => 'success', 'message' => 'Data Barang baru berhasil disimpan (ID: ' . $id . ')'];

        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['status' => 'error', 'message' => 'Gagal menyimpan data: ' . $e->getMessage()];
        }
    }

    // --- UPDATE (Replikasi updateData) ---
    public function updateData(array $data) {
        try {
            $this->conn->beginTransaction();

            // 1. UPDATE T_BARANG
            $sqlBarang = "UPDATE T_BARANG SET 
                          NAMABRG = :namabrg, KODESP = :kodesp, KODEJN = :kodejn, KODEMR = :kodemr, 
                          KODEST = :kodest, HGBELI = :hgbeli, HGJUAL = :hgjual, DISC = :disc, 
                          MARKUP = :markup, UUSER = :uuser, UKOMP = :ukomp, UDATE = GETDATE()
                          WHERE KODEBRG = :kodebrg_id"; 
            $stmtBarang = $this->conn->prepare($sqlBarang);
            $stmtBarang->execute([
                ':namabrg' => $data['NAMABRG'], ':kodesp' => $data['KODESP'], ':kodejn' => $data['KODEJN'], 
                ':kodemr' => $data['KODEMR'], ':kodest' => $data['KODEST'], ':hgbeli' => $data['HGBELI'], 
                ':hgjual' => $data['HGJUAL'], ':disc' => $data['DISC'], ':markup' => $data['MARKUP'], 
                ':uuser' => $data['UUSER'], ':ukomp' => $data['UKOMP'],
                ':kodebrg_id' => $data['KODEBRG']
            ]);

            // 2. UPDATE T_STOK (Hanya update min/max)
            $sqlStok = "UPDATE T_STOK SET STOKMIN = :stokmin, STOKMAX = :stokmax 
                        WHERE ID = (SELECT ID FROM T_BARANG WHERE KODEBRG = :kodebrg_id)";
            $stmtStok = $this->conn->prepare($sqlStok);
            $stmtStok->execute([
                ':stokmin' => $data['STOKMIN'], 
                ':stokmax' => $data['STOKMAX'],
                ':kodebrg_id' => $data['KODEBRG']
            ]);

            $this->conn->commit();
            return ['status' => 'success', 'message' => 'Data Barang berhasil diperbarui.'];

        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['status' => 'error', 'message' => 'Gagal memperbarui data: ' . $e->getMessage()];
        }
    }
}
?>