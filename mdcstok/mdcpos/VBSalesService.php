<?php
// FILE: VBSalesService.php
require_once 'db_connection.php';

class VBSalesService {
    private $conn;

    public function __construct($pdo_connection) {
        $this->conn = $pdo_connection;
    }

    public function simpanTransaksiJual(array $headerData, array $detailItems) {
        
        if ($this->conn === null) {
            return ['status' => 'error', 'message' => 'Database connection is not available.'];
        }
        
        // Pastikan NOTA unik
        $nota = 'TRX' . date('ymdHis') . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        
        try {
            // 1. Format detail items (TVP) as T-SQL VALUES list
            // Karena PDO SQLSRV tidak mendukung TVP secara langsung, kita membentuk
            // batch T-SQL: DECLARE @DetailItems AS dbo.Type_DetailJual; INSERT ...; EXEC SP ...
            $tvpValues = [];
            foreach ($detailItems as $item) {
                // Validasi minimal dan normalisasi tipe angka
                $ID       = (int)($item['ID'] ?? 0);
                $QTY      = (int)($item['QTY'] ?? 0);
                $HGJUAL   = number_format((float)($item['HGJUAL'] ?? 0), 2, '.', '');
                $HGBELI   = number_format((float)($item['HGBELI'] ?? 0), 2, '.', '');
                $BRUTO    = number_format((float)($item['BRUTO'] ?? 0), 2, '.', '');
                $DISC1    = number_format((float)($item['DISC1'] ?? 0), 2, '.', '');
                $DISC2    = number_format((float)($item['DISC2'] ?? 0), 2, '.', '');
                $HITDISC1 = number_format((float)($item['HITDISC1'] ?? 0), 2, '.', '');
                $HITDISC2 = number_format((float)($item['HITDISC2'] ?? 0), 2, '.', '');
                $NETTO    = number_format((float)($item['NETTO'] ?? 0), 2, '.', '');

                // Susun satu baris VALUES sesuai urutan kolom di tipe tabel SQL Server
                $tvpValues[] = "($ID, $QTY, $HGJUAL, $HGBELI, $BRUTO, $DISC1, $DISC2, $HITDISC1, $HITDISC2, $NETTO)";
            }
            if (empty($tvpValues)) {
                return ['status' => 'error', 'message' => 'Detail transaksi kosong.'];
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
            
            // 3. Susun batch T-SQL: deklarasi TVP lokal, isi data, lalu panggil SP.
            // Catatan: urutan kolom harus sesuai dengan definisi tipe dbo.Type_DetailJual
            $valuesSql = implode(",\n                            ", $tvpValues);
            $sql = "
                DECLARE @DetailItems dbo.Type_DetailJual;\n
                INSERT INTO @DetailItems (ID, QTY, HGJUAL, HGBELI, BRUTO, DISC1, DISC2, HITDISC1, HITDISC2, NETTO)\n
                VALUES\n
                $valuesSql;\n
                EXEC [dbo].[SP_SIMPAN_JUAL]\n
                    @NONOTA = ?,\n
                    @KODECB = ?,\n
                    @KODESL = ?,\n
                    @KODEMB = ?,\n
                    @CARABAYAR = ?,\n
                    @STATUS = ?,\n
                    @BULAT = ?,\n
                    @RETUR = ?,\n
                    @BAYAR = ?,\n
                    @GROSIR = ?,\n
                    @REFCARD = ?,\n
                    @POTADMIN = ?,\n
                    @CUSER = ?,\n
                    @DetailItems = @DetailItems;";
            
            $stmt = $this->conn->prepare($sql);
            
            // 4. Binding Parameter Skalar (Posisi 1-13)
            for ($i = 0; $i < count($params); $i++) {
                // Gunakan tipe data PDO generik
                $stmt->bindParam($i + 1, $params[$i]); 
            }

            // 5. Tidak ada binding untuk TVP. TVP disiapkan dalam batch T-SQL di atas.
            
            // 6. Eksekusi
            $stmt->execute();
            
            // Lanjutkan dengan logika pengecekan hasil SP
            
            return ['status' => 'success', 'message' => 'Transaksi Jual berhasil disimpan dengan Nota: ' . $nota];

        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Gagal Transaksi: ' . $e->getMessage()];
        }
    }
}
?>
