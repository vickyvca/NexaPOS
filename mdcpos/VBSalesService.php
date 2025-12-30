<?php
// FILE: VBSalesService.php
require_once 'db_connection.php';

class VBSalesService {
    private $conn;

    public function __construct($pdo_connection) {
        $this->conn = $pdo_connection;
    }

    /**
     * Mereplikasi cmdSimpan_MouseDown di frmBayar (Panggilan SP Transaksi)
     */
    public function simpanTransaksiJual(array $headerData, array $detailItems) {
        
        if ($this->conn === null) {
            return ['status' => 'error', 'message' => 'Database connection is not available.'];
        }
        
        $nota = 'TRX' . date('ymdHis') . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        
        try {
            // 1. Format detail items (TVP) - Array of Arrays
            $tvp_params = [];
            foreach ($detailItems as $item) {
                // Urutan array ini HARUS SESUAI dengan Type_DetailJual SQL Server
                $tvp_params[] = [
                    $item['ID'], $item['QTY'], $item['HGJUAL'], $item['HGBELI'], 
                    $item['BRUTO'], $item['DISC1'], $item['DISC2'], $item['HITDISC1'], 
                    $item['HITDISC2'], $item['NETTO']
                ];
            }
            
            // 2. Siapkan array parameter skalar (menggunakan reference untuk binding)
            $params = [
                &$nota,
                &$headerData['KODECB'],
                &$headerData['KODESL'],
                &$headerData['KODEMB'],
                &$headerData['CARABAYAR'],
                &$headerData['STATUS'],
                &$headerData['BULAT'],
                &$headerData['RETUR'],
                &$headerData['BAYAR'],
                &$headerData['GROSIR'],
                &$headerData['REFCARD'],
                &$headerData['POTADMIN'],
                &$headerData['CUSER']
            ];
            
            // 3. Panggil SP dengan placeholders
            $sql = "EXEC [dbo].[SP_SIMPAN_JUAL] 
                        @NONOTA = ?, 
                        @KODECB = ?, 
                        @KODESL = ?, 
                        @KODEMB = ?, 
                        @CARABAYAR = ?, 
                        @STATUS = ?, 
                        @BULAT = ?, 
                        @RETUR = ?, 
                        @BAYAR = ?, 
                        @GROSIR = ?, 
                        @REFCARD = ?, 
                        @POTADMIN = ?, 
                        @CUSER = ?, 
                        @DetailItems = ?"; 
            
            $stmt = $this->conn->prepare($sql);
            
            // 4. Binding Parameter Skalar (Posisi 1-13)
            for ($i = 0; $i < count($params); $i++) {
                $stmt->bindParam($i + 1, $params[$i]); 
            }

            // 5. Binding Parameter TVP (Posisi 14)
            $stmt->bindParam(14, $tvp_params, PDO::PARAM_INPUT_OUTPUT, 0, 'Type_DetailJual');
            
            $stmt->execute();
            
            return ['status' => 'success', 'message' => 'Transaksi Jual berhasil disimpan dengan Nota: ' . $nota];

        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Gagal Transaksi: ' . $e->getMessage()];
        }
    }
}
?>