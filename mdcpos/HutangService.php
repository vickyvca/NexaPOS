<?php
// FILE: HutangService.php
require_once 'db_connection.php';

class HutangService {
    private $conn;

    public function __construct($pdo_connection) {
        $this->conn = $pdo_connection;
    }

    // --- PROSES OTOMATIS HUTANG JATUH TEMPO (Cron Job Logic) ---
    public function prosesJatuhTempoOtomatis($cUser) {
        if ($this->conn === null) {
            return ['status' => 'error', 'message' => 'Database connection is not available.'];
        }
        
        try {
            // T-SQL Batch: Logika PROSESHTG
            $sql = "
                SET NOCOUNT ON;
                
                DECLARE @NILAI FLOAT, @AKHUTANG VARCHAR(20), @AKBUFFER VARCHAR(20), 
                        @NOTA AS VARCHAR(20), @TGL AS DATETIME, @KET AS VARCHAR(50);
                
                SELECT @AKHUTANG = BAYARHTGDAGANG, @AKBUFFER = BUFFERHTGDAGANG FROM SET_AKUN;
                SET @TGL = CONVERT(DATE, SYSDATETIME());
                SET @NOTA = 'PR.' + CONVERT(VARCHAR, @TGL, 12); 
                SET @KET = 'PROSES HUTANG ' + CONVERT(VARCHAR, GETDATE(), 103) + ' ' + CONVERT(VARCHAR(8), SYSDATETIME(), 8);
                
                SET @NILAI = ISNULL((
                    SELECT SUM(M.NILAI) 
                    FROM HIS_MUTAKUN M 
                    INNER JOIN HIS_BAYARHUTANG B ON M.NONOTA = B.NONOTA 
                    INNER JOIN HIS_PROSESHUTANG P ON B.NONOTA = P.NOTAHUTANG 
                    WHERE P.NOTAPROSES IS NULL AND B.TGLJT <= GETDATE() AND M.KODEAK = @AKBUFFER
                ), 0);
                
                IF @NILAI <> 0
                BEGIN
                    BEGIN TRANSACTION;
                    
                    -- Jurnal DEBET Buffer Hutang, KREDIT Hutang Dagang
                    INSERT INTO HIS_MUTAKUN (NONOTA, KODEAK, KODECB, TGL, NILAI, OPR, JENIS, KET, CUSER, CKOMP, CDATE)
                    VALUES (@NOTA, @AKBUFFER, '00', @TGL, @NILAI, 'D', 6, @KET, 'SYSTEM', 'PHP_CRON', GETDATE());
                    
                    INSERT INTO HIS_MUTAKUN (NONOTA, KODEAK, KODECB, TGL, NILAI, OPR, JENIS, KET, CUSER, CKOMP, CDATE)
                    VALUES (@NOTA, @AKHUTANG, '00', @TGL, @NILAI, 'K', 6, @KET, 'SYSTEM', 'PHP_CRON', GETDATE());
                    
                    -- UPDATE Saldo T_AKUN 
                    UPDATE T_AKUN SET SALDO = SALDO + @NILAI WHERE KODEAK = @AKBUFFER; 
                    UPDATE T_AKUN SET SALDO = SALDO - @NILAI WHERE KODEAK = @AKHUTANG;
                    
                    -- UPDATE status proses di HIS_PROSESHUTANG
                    UPDATE P SET NOTAPROSES = @NOTA, UDATE = GETDATE(), UUSER = @cUser
                    FROM HIS_BAYARHUTANG B INNER JOIN HIS_PROSESHUTANG P ON B.NONOTA = P.NOTAHUTANG
                    WHERE NOTAPROSES IS NULL AND B.TGLJT <= GETDATE();
                    
                    COMMIT TRANSACTION;
                    
                    SELECT 'success' as Status, 'Proses Hutang Jatuh Tempo selesai.' as Message;
                END
                ELSE
                BEGIN
                    SELECT 'info' as Status, 'Tidak ada hutang jatuh tempo yang diproses.' as Message;
                END
            ";
            
            $stmt = $this->conn->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            return ['status' => $result['Status'] ?? 'info', 'message' => $result['Message'] ?? 'Proses Hutang Jatuh Tempo selesai.'];
            
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return ['status' => 'error', 'message' => 'Gagal Proses Hutang: ' . $e->getMessage()];
        }
    }

    // --- LOAD LIST HUTANG ---
    public function loadListHutang($kodeSP) {
        try {
            $sql = "
                SELECT 
                    H.NONOTA, H.TGL, H.TGLJTO, H.TOTALHTG, H.UANGMUKA, H.SISAHTG, S.NAMASP
                FROM HIS_HUTANG H
                INNER JOIN T_SUPLIER S ON H.KODESP = S.KODESP
                WHERE H.KODESP = ? AND H.STATUS <> 1
                ORDER BY H.TGLJTO ASC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(1, $kodeSP, PDO::PARAM_STR);
            $stmt->execute();
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['status' => 'success', 'data' => $results];

        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Gagal memuat list hutang: ' . $e->getMessage()];
        }
    }


    /**
     * Finalisasi Pembayaran Hutang Dagang (Panggilan SP_BAYAR_HUTANG)
     */
    public function prosesOtorisasiHutang(array $headerData, array $detailHutang) {
        
        if ($this->conn === null) {
            return ['status' => 'error', 'message' => 'Koneksi database tidak tersedia.'];
        }
        
        $notaBayar = 'BHT' . date('ymdHis') . str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);

        try {
            // 1. Format detail items (TVP) - Array of Arrays
            $tvpValues = [];
            foreach ($detailHutang as $item) {
                $notaHutang = isset($item['NONOTA_HUTANG']) ? (string)$item['NONOTA_HUTANG'] : '';
                $nilaiBayar = number_format((float)($item['NILAI_BAYAR'] ?? 0), 2, '.', '');
                $notaQuoted = $this->conn->quote($notaHutang); // Escape string
                $tvpValues[] = "($notaQuoted, $nilaiBayar)";
            }
            if (empty($tvpValues)) {
                 return ['status' => 'error', 'message' => 'Detail hutang kosong.'];
            }
            
            // 2. Definisi Parameter Skalar (Menggunakan reference)
            $params = [
                &$notaBayar,
                &$headerData['KODECB'],
                &$headerData['KODESP'],
                &$headerData['TGL'],
                &$headerData['NILAI_KAS_BAYAR'],
                &$headerData['NILAI_RETUR_POTONGAN'],
                &$headerData['IDAK_KAS'],
                &$headerData['KETERANGAN'],
                &$headerData['CUSER']
            ];
            
            // 3. Susun batch T-SQL: deklarasi TVP lokal, isi data, lalu panggil SP
            $valuesSql = implode(",\n", $tvpValues);
            $sql = "
                DECLARE @DETAIL_HUTANG_TVP dbo.Type_DetailHutang;
                INSERT INTO @DETAIL_HUTANG_TVP (NONOTA_HUTANG, NILAI_BAYAR)
                VALUES {$valuesSql};
                EXEC [dbo].[SP_BAYAR_HUTANG] 
                    @NONOTA_BAYAR = ?, @KODECB = ?, @KODESP = ?, @TGL = ?, 
                    @NILAI_KAS_BAYAR = ?, @NILAI_RETUR_POTONGAN = ?, @IDAK_KAS = ?, 
                    @KETERANGAN = ?, @CUSER = ?, @DETAIL_HUTANG_TVP = @DETAIL_HUTANG_TVP;"; 
            
            $stmt = $this->conn->prepare($sql);
            
            // 4. Binding Parameter Skalar
            for ($i = 0; $i < count($params); $i++) {
                $stmt->bindParam($i + 1, $params[$i]); 
            }
            
            $stmt->execute();
            
            return ['status' => 'success', 'message' => 'Pembayaran Hutang berhasil dengan Nota: ' . $notaBayar];

        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Gagal Proses Pembayaran Hutang: ' . $e->getMessage()];
        }
    }
}
?>