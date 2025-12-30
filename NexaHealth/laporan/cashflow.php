<?php
require_once __DIR__ . '/../middleware.php';
ensure_role(['admin','owner']);
$pdo = getPDO();
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to'] ?? date('Y-m-d');

$rows = $pdo->prepare("SELECT date, type, SUM(amount) amt FROM cashbooks WHERE date BETWEEN ? AND ? GROUP BY date, type ORDER BY date");
$rows->execute([$from,$to]);
$grouped = [];
foreach ($rows as $r) {
    if (!isset($grouped[$r['date']])) $grouped[$r['date']] = ['in'=>0,'out'=>0];
    $grouped[$r['date']][$r['type']] = $r['amt'];
}

if (isset($_GET['export']) && $_GET['export']==='csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="cashflow.csv"');
    $out = fopen('php://output','w');
    fputcsv($out,['Tanggal','Masuk','Keluar','Net']);
    foreach ($grouped as $d=>$v) {
        $net = ($v['in']??0)-($v['out']??0);
        fputcsv($out,[$d,$v['in']??0,$v['out']??0,$net]);
    }
    exit;
}

$totalIn = array_sum(array_column($grouped,'in'));
$totalOut = array_sum(array_column($grouped,'out'));
$saldo = $totalIn - $totalOut;
?>
<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="d-flex justify-content-between align-items-center">
    <h4>Cash Flow</h4>
    <div class="no-print">
        <a class="btn btn-outline-light btn-sm" href="?from=<?= $from; ?>&to=<?= $to; ?>&export=csv"><i class="bi bi-download"></i> CSV</a>
        <button class="btn btn-secondary btn-sm" onclick="window.print();return false;"><i class="bi bi-printer"></i> Print</button>
    </div>
</div>
<form class="row g-2 mb-3 no-print">
    <div class="col-md-3"><input type="date" class="form-control" name="from" value="<?= $from; ?>"></div>
    <div class="col-md-3"><input type="date" class="form-control" name="to" value="<?= $to; ?>"></div>
    <div class="col-md-2"><button class="btn btn-primary">Tampilkan</button></div>
</form>
<div class="report-area mb-3">
    <div class="d-flex justify-content-between">
        <div>Masuk: <strong><?= format_rupiah($totalIn); ?></strong></div>
        <div>Keluar: <strong><?= format_rupiah($totalOut); ?></strong></div>
        <div>Net: <strong><?= format_rupiah($saldo); ?></strong></div>
    </div>
</div>
<div class="table-responsive">
    <table class="table table-dark table-striped table-sm">
        <thead><tr><th>#</th><th>Tanggal</th><th>Masuk</th><th>Keluar</th><th>Net</th></tr></thead>
        <tbody>
            <?php $i=1; foreach ($grouped as $d=>$v): $net=($v['in']??0)-($v['out']??0); ?>
            <tr>
                <td><?= $i++; ?></td>
                <td><?= $d; ?></td>
                <td><?= format_rupiah($v['in']??0); ?></td>
                <td><?= format_rupiah($v['out']??0); ?></td>
                <td><?= format_rupiah($net); ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$grouped): ?>
            <tr><td colspan="5" class="text-center text-muted">Belum ada data</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
