<?php
require_once __DIR__ . '/../middleware.php';
ensure_role(['admin','owner']);
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$pdo = getPDO();
$stmt = $pdo->prepare("SELECT si.qty, si.price, i.buy_price FROM sale_items si JOIN sales s ON s.id=si.sale_id JOIN items i ON i.id=si.item_id WHERE s.date BETWEEN ? AND ?");
$stmt->execute([$from,$to]);
$rows = $stmt->fetchAll();
$laba = 0;
foreach ($rows as $r) {
    $laba += ($r['price'] - $r['buy_price']) * $r['qty'];
}
?>
<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="d-flex justify-content-between align-items-center">
    <h4>Laporan Laba Kotor</h4>
    <div class="no-print">
        <button class="btn btn-secondary btn-sm" onclick="window.print();return false;">Print</button>
    </div>
</div>
<form class="row g-2 mb-3 no-print">
    <div class="col-md-3"><input type="date" class="form-control" name="from" value="<?= $from; ?>"></div>
    <div class="col-md-3"><input type="date" class="form-control" name="to" value="<?= $to; ?>"></div>
    <div class="col-md-2"><button class="btn btn-secondary">Tampilkan</button></div>
</form>
<div class="report-area">
<div class="alert alert-info">Periode <?= $from; ?> s/d <?= $to; ?></div>
<h5>Laba Kotor: <?= format_rupiah($laba); ?></h5>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
