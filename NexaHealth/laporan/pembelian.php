<?php
require_once __DIR__ . '/../middleware.php';
ensure_role(['admin','kasir','owner']);
$pdo = getPDO();
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$supplier = $_GET['supplier'] ?? '';
$params = [$from,$to];
$where = "WHERE p.date BETWEEN ? AND ?";
if ($supplier) { $where.=" AND p.supplier_id=?"; $params[]=$supplier; }
$sql = "SELECT p.*, s.name supplier FROM purchases p LEFT JOIN suppliers s ON s.id=p.supplier_id $where ORDER BY p.date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=pembelian.csv');
    $out = fopen('php://output','w');
    fputcsv($out,['Tanggal','No','Supplier','Total','Status']);
    foreach ($rows as $r) fputcsv($out,[$r['date'],$r['purchase_no'],$r['supplier'],$r['total'],$r['status']]);
    exit;
}
$suppliers = fetch_options('suppliers');
?>
<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="d-flex justify-content-between align-items-center">
    <h4>Laporan Pembelian</h4>
    <div class="no-print">
        <a class="btn btn-success btn-sm" href="?from=<?= $from; ?>&to=<?= $to; ?>&supplier=<?= $supplier; ?>&export=1">Export CSV</a>
        <button class="btn btn-secondary btn-sm" onclick="window.print();return false;">Print</button>
    </div>
</div>
<form class="row g-2 mb-3 no-print">
    <div class="col-md-2"><input type="date" class="form-control" name="from" value="<?= $from; ?>"></div>
    <div class="col-md-2"><input type="date" class="form-control" name="to" value="<?= $to; ?>"></div>
    <div class="col-md-3">
        <select class="form-select" name="supplier"><option value="">Semua Supplier</option>
            <?php foreach ($suppliers as $s): ?><option value="<?= $s['id']; ?>" <?= $supplier==$s['id']?'selected':''; ?>><?= htmlspecialchars($s['name']); ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-secondary">Tampilkan</button></div>
</form>
<div class="report-area">
<table class="table table-sm table-hover align-middle">
    <thead class="table-dark"><tr><th>No</th><th>Tanggal</th><th>No Pembelian</th><th>Supplier</th><th class="text-end">Total</th><th>Status</th></tr></thead>
    <tbody>
    <?php $tot=0; $no=1; foreach ($rows as $r): $tot+=$r['total']; ?>
    <tr>
        <td><?= $no++; ?></td>
        <td><?= $r['date']; ?></td>
        <td><?= htmlspecialchars($r['purchase_no']); ?></td>
        <td><?= htmlspecialchars($r['supplier']); ?></td>
        <td class="text-end"><?= format_rupiah($r['total']); ?></td>
        <td><span class="badge bg-<?= $r['status']=='posted'?'success':'secondary'; ?>"><?= htmlspecialchars($r['status']); ?></span></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<div class="text-end fw-bold">Total: <?= format_rupiah($tot); ?></div>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
