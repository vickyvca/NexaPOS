<?php
require_once __DIR__ . '/../middleware.php';
ensure_role(['admin','kasir','owner']);
$pdo = getPDO();
$itemId = $_GET['item_id'] ?? '';
$where = $itemId ? "WHERE sm.item_id=?" : '';
$params = $itemId ? [$itemId] : [];
$logs = $pdo->prepare("SELECT sm.*, i.name FROM stock_moves sm JOIN items i ON i.id=sm.item_id $where ORDER BY sm.date DESC, sm.id DESC LIMIT 200");
$logs->execute($params);
$items = fetch_options('items');
$lowStock = $pdo->query("SELECT name, stock, min_stock FROM items WHERE stock<=min_stock")->fetchAll();
?>
<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="d-flex justify-content-between align-items-center">
    <h4>Laporan Stok</h4>
    <div class="no-print">
        <button class="btn btn-secondary btn-sm" onclick="window.print();return false;">Print</button>
    </div>
</div>
<form class="row g-2 mb-3 no-print">
    <div class="col-md-6">
        <select class="form-select" name="item_id">
            <option value="">Semua Barang</option>
            <?php foreach ($items as $i): ?><option value="<?= $i['id']; ?>" <?= $itemId==$i['id']?'selected':''; ?>><?= htmlspecialchars($i['name']); ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-secondary">Tampilkan</button></div>
</form>
<div class="report-area">
<table class="table table-sm table-hover align-middle">
    <thead class="table-dark"><tr><th>No</th><th>Tanggal</th><th>Barang</th><th>Ref</th><th class="text-end">Qty In</th><th class="text-end">Qty Out</th><th>Catatan</th></tr></thead>
    <tbody>
    <?php $no=1; foreach ($logs as $l): ?>
    <tr>
        <td><?= $no++; ?></td>
        <td><?= $l['date']; ?></td>
        <td><?= htmlspecialchars($l['name']); ?></td>
        <td><?= htmlspecialchars($l['ref_type'].'#'.$l['ref_id']); ?></td>
        <td class="text-end"><?= $l['qty_in']; ?></td>
        <td class="text-end"><?= $l['qty_out']; ?></td>
        <td><?= htmlspecialchars($l['note']); ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<h6>Stok Menipis</h6>
<table class="table table-sm table-hover align-middle">
    <thead class="table-dark"><tr><th>No</th><th>Barang</th><th class="text-end">Stok</th><th class="text-end">Min</th></tr></thead>
    <tbody>
    <?php $no=1; foreach ($lowStock as $l): ?>
    <tr><td><?= $no++; ?></td><td><?= htmlspecialchars($l['name']); ?></td><td class="text-end"><?= $l['stock']; ?></td><td class="text-end"><?= $l['min_stock']; ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
