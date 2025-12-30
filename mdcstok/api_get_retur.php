<?php
require_once __DIR__ . '/api_common.php';
require_roles(['supplier']);

$nobukti = $_GET['nobukti'] ?? '';
if (!$nobukti) {
    json_ok(['data' => []]);
}

$stmt = $conn->prepare("SELECT NONOTA FROM HIS_RETURBELI WHERE NOBUKTI = :nobukti");
$stmt->execute(['nobukti' => $nobukti]);
$retur = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$retur) {
    json_ok(['data' => []]);
}

$nonota = $retur['NONOTA'];
$stmt2 = $conn->prepare("
    SELECT T.NAMABRG + ' ' + T.ARTIKELBRG AS BARANG, D.HGBELI,
           D.QTY00 + D.QTY01 + D.QTY02 + D.QTY03 + D.QTY04 + D.QTY99 AS TTL,
           D.HGBELI * (D.QTY00 + D.QTY01 + D.QTY02 + D.QTY03 + D.QTY04 + D.QTY99) AS NILAI
    FROM HIS_DTRETURBELI D
    INNER JOIN T_BARANG T ON T.ID = D.ID
    WHERE D.NONOTA = :nonota
");
$stmt2->execute(['nonota' => $nonota]);
$detail = $stmt2->fetchAll(PDO::FETCH_ASSOC) ?: [];

json_ok(['data' => $detail]);
