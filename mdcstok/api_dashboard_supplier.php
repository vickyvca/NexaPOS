<?php
require_once __DIR__ . '/api_common.php';
require_roles(['supplier']);

function get_sold_pcs_by_range(PDO $conn, string $kodesp, string $start, string $end): float {
    try {
        $stmt = $conn->prepare("SELECT SUM(QTY) as total FROM V_JUAL WHERE KODESP = :k AND TGL BETWEEN :s AND :e");
        $stmt->execute([':k' => $kodesp, ':s' => $start, ':e' => $end]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)($result['total'] ?? 0);
    } catch (PDOException $e) {
        return 0;
    }
}

$kodesp = $_SESSION['kodesp'] ?? '';
if (!$kodesp) {
    json_error('Kode supplier tidak ditemukan.', 401);
}

if (empty($_SESSION['namasp'])) {
    try {
        $stmt_nama = $conn->prepare("SELECT NAMASP FROM T_SUPLIER WHERE KODESP = :k");
        $stmt_nama->execute([':k' => $kodesp]);
        $r = $stmt_nama->fetch(PDO::FETCH_ASSOC);
        $_SESSION['namasp'] = $r && !empty($r['NAMASP']) ? trim($r['NAMASP']) : 'Supplier';
    } catch (PDOException $e) {
        $_SESSION['namasp'] = 'Supplier';
    }
}

$today = date('Y-m-d');
$pcs_bulan_ini = get_sold_pcs_by_range($conn, $kodesp, date('Y-m-01'), date('Y-m-t'));
$pcs_mtd_ini = get_sold_pcs_by_range($conn, $kodesp, date('Y-m-01'), $today);
$pcs_mtd_lalu = get_sold_pcs_by_range($conn, $kodesp, date('Y-m-01', strtotime('last month')), date('Y-m-d', strtotime('last month')));
$pcs_3bulan_ini = get_sold_pcs_by_range($conn, $kodesp, date('Y-m-d', strtotime('-89 days')), $today);
$pcs_3bulan_lalu = get_sold_pcs_by_range($conn, $kodesp, date('Y-m-d', strtotime('-179 days')), date('Y-m-d', strtotime('-90 days')));

$lima_nota_terakhir = [];
try {
    $stmt_nota = $conn->prepare(
        "SELECT TOP 5 H.NONOTA, B.NOBUKTI, B.TGL, H.TOTALHTG, H.STATUS
         FROM HIS_HUTANG H
         INNER JOIN HIS_BELI B ON B.NONOTA = H.NONOTA
         WHERE B.KODESP = :k
         ORDER BY B.TGL DESC"
    );
    $stmt_nota->execute([':k' => $kodesp]);
    $lima_nota_terakhir = $stmt_nota->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    $lima_nota_terakhir = [];
}

json_ok([
    'data' => [
        'supplier' => [
            'kodesp' => $kodesp,
            'namasp' => $_SESSION['namasp'] ?? 'Supplier',
            'pcsBulanIni' => $pcs_bulan_ini,
            'pcsMTDIni' => $pcs_mtd_ini,
            'pcsMTDLalu' => $pcs_mtd_lalu,
            'pcs3BulanIni' => $pcs_3bulan_ini,
            'pcs3BulanLalu' => $pcs_3bulan_lalu,
        ],
        'limaNotaTerakhir' => array_map(function ($row) {
            return [
                'nonota' => $row['NONOTA'] ?? '',
                'nobukti' => $row['NOBUKTI'] ?? '',
                'tgl' => !empty($row['TGL']) ? date('Y-m-d', strtotime($row['TGL'])) : '',
                'totalhtg' => (float)($row['TOTALHTG'] ?? 0),
                'status' => (int)($row['STATUS'] ?? 0),
            ];
        }, $lima_nota_terakhir),
    ],
]);
