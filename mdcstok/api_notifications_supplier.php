<?php
require_once __DIR__ . '/api_common.php';
require_roles(['supplier']);

$kodesp = $_SESSION['kodesp'] ?? '';
if (!$kodesp) {
    json_error('Supplier tidak teridentifikasi.', 401);
}

try {
    $stmt = $conn->prepare("
        SELECT TOP 10
            ID,
            Pesan,
            Link,
            ReferensiID,
            StatusKirimWA,
            SudahDilihat,
            WaktuDibuat
        FROM T_NOTIFIKASI
        WHERE PenerimaID = :kodesp AND TipePenerima = 'supplier'
        ORDER BY WaktuDibuat DESC
    ");
    $stmt->execute([':kodesp' => $kodesp]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $notifications = array_map(function ($row) {
        return [
            'id' => (int)$row['ID'],
            'message' => $row['Pesan'],
            'link' => $row['Link'],
            'reference' => $row['ReferensiID'],
            'status' => $row['StatusKirimWA'],
            'read' => (bool)$row['SudahDilihat'],
            'createdAt' => $row['WaktuDibuat'] ? date(DATE_ATOM, strtotime($row['WaktuDibuat'])) : null,
        ];
    }, $rows);
    json_ok(['data' => ['notifications' => $notifications]]);
} catch (PDOException $e) {
    json_error('Gagal memuat notifikasi: ' . $e->getMessage(), 500);
}
