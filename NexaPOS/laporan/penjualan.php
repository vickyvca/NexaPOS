<?php
require_once __DIR__ . '/../middleware.php';
ensure_role(['admin','kasir','owner']);
$pdo = getPDO();
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$kasir = $_GET['kasir'] ?? '';
$cat = $_GET['cat'] ?? '';
$export = isset($_GET['export']);
$params = [$from,$to];
$where = "WHERE s.date BETWEEN ? AND ?";
if ($kasir) { $where .= " AND s.created_by=?"; $params[]=$kasir; }
if ($cat) { $where .= " AND i.category_id=?"; $params[]=$cat; }
$sql = "SELECT s.date, s.sale_no, u.name kasir, i.name item, si.qty, si.price, si.discount
        FROM sales s
        JOIN sale_items si ON si.sale_id=s.id
        JOIN items i ON i.id=si.item_id
        LEFT JOIN users u ON u.id=s.created_by
        $where ORDER BY s.date DESC";
$stmt = getPDO()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();
if ($export) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=penjualan.csv');
    $out = fopen('php://output','w');
    fputcsv($out,['Tanggal','No','Kasir','Item','Qty','Harga','Diskon','Subtotal']);
    foreach ($rows as $r) {
        fputcsv($out,[$r['date'],$r['sale_no'],$r['kasir'],$r['item'],$r['qty'],$r['price'],$r['discount'], ($r['qty']*$r['price'])-$r['discount']]);
    }
    exit;
}
$users = fetch_options('users');
$cats = fetch_options('categories');
?>
<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="d-flex justify-content-between align-items-center">
    <h4>Laporan Penjualan</h4>
    <div class="no-print">
        <a class="btn btn-success btn-sm" href="?from=<?= $from; ?>&to=<?= $to; ?>&kasir=<?= $kasir; ?>&cat=<?= $cat; ?>&export=1">Export CSV</a>
        <button class="btn btn-secondary btn-sm" onclick="window.print();return false;">Print</button>
    </div>
</div>
<form class="row g-2 mb-3 no-print">
    <div class="col-md-2"><input type="date" class="form-control" name="from" value="<?= $from; ?>"></div>
    <div class="col-md-2"><input type="date" class="form-control" name="to" value="<?= $to; ?>"></div>
    <div class="col-md-2">
        <select class="form-select" name="kasir"><option value="">Semua Kasir</option>
            <?php foreach ($users as $u): ?><option value="<?= $u['id']; ?>" <?= $kasir==$u['id']?'selected':''; ?>><?= htmlspecialchars($u['name']); ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select class="form-select" name="cat"><option value="">Semua Kategori</option>
            <?php foreach ($cats as $c): ?><option value="<?= $c['id']; ?>" <?= $cat==$c['id']?'selected':''; ?>><?= htmlspecialchars($c['name']); ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-secondary">Tampilkan</button></div>
</form>
<div class="report-area">
<table class="table table-sm table-hover align-middle">
    <thead class="table-dark"><tr><th>No</th><th>Tanggal</th><th>No Trx</th><th>Kasir</th><th>Item</th><th class="text-end">Qty</th><th class="text-end">Harga</th><th class="text-end">Diskon</th><th class="text-end">Subtotal</th></tr></thead>
    <tbody>
    <?php $tot=0; $no=1; foreach ($rows as $r): $sub=($r['qty']*$r['price'])-$r['discount']; $tot+=$sub; ?>
    <tr>
        <td><?= $no++; ?></td>
        <td><?= $r['date']; ?></td>
        <td><?= htmlspecialchars($r['sale_no']); ?></td>
        <td><?= htmlspecialchars($r['kasir']); ?></td>
        <td><?= htmlspecialchars($r['item']); ?></td>
        <td class="text-end"><?= $r['qty']; ?></td>
        <td class="text-end"><?= format_rupiah($r['price']); ?></td>
        <td class="text-end"><?= format_rupiah($r['discount']); ?></td>
        <td class="text-end"><?= format_rupiah($sub); ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<div class="text-end fw-bold">Total: <?= format_rupiah($tot); ?></div>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
