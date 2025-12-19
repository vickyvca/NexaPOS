<?php
require_once __DIR__ . '/../middleware.php';
ensure_role(['admin','owner']);
$pdo = getPDO();
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to'] ?? date('Y-m-d');

// Revenue dari penjualan
$revStmt = $pdo->prepare("SELECT SUM(grand_total) FROM sales WHERE date BETWEEN ? AND ?");
$revStmt->execute([$from,$to]);
$revenue = $revStmt->fetchColumn() ?: 0;

// Other income dari cashbook yang bukan penjualan
$incStmt = $pdo->prepare("SELECT SUM(amount) FROM cashbooks WHERE type='in' AND ref_type!='sale' AND date BETWEEN ? AND ?");
$incStmt->execute([$from,$to]);
$otherIncome = $incStmt->fetchColumn() ?: 0;

// COGS menggunakan harga beli terakhir
$cogsStmt = $pdo->prepare("SELECT si.qty, i.buy_price FROM sale_items si JOIN sales s ON s.id=si.sale_id JOIN items i ON i.id=si.item_id WHERE s.date BETWEEN ? AND ?");
$cogsStmt->execute([$from,$to]);
$cogs = 0;
foreach ($cogsStmt as $c) {
    $cogs += $c['qty'] * $c['buy_price'];
}

// Operating expense dari cashbook (selain pembelian & gaji)
$expStmt = $pdo->prepare("SELECT SUM(amount) FROM cashbooks WHERE type='out' AND ref_type NOT IN ('purchase','salary') AND date BETWEEN ? AND ?");
$expStmt->execute([$from,$to]);
$opex = $expStmt->fetchColumn() ?: 0;

// Salaries
$salStmt = $pdo->prepare("SELECT SUM(amount) FROM salaries WHERE date_paid BETWEEN ? AND ?");
$salStmt->execute([$from,$to]);
$salaryTotal = $salStmt->fetchColumn() ?: 0;

$grossProfit = $revenue - $cogs;
$operatingProfit = $grossProfit + $otherIncome - $opex - $salaryTotal;
?>
<?php include __DIR__ . '/../layout/header.php'; ?>
<div class="d-flex justify-content-between align-items-center">
    <h4>Laporan Laba Rugi</h4>
    <div class="no-print">
        <button class="btn btn-secondary btn-sm" onclick="window.print();return false;"><i class="bi bi-printer"></i> Print</button>
    </div>
</div>
<form class="row g-2 mb-3 no-print">
    <div class="col-md-3"><input type="date" class="form-control" name="from" value="<?= $from; ?>"></div>
    <div class="col-md-3"><input type="date" class="form-control" name="to" value="<?= $to; ?>"></div>
    <div class="col-md-2"><button class="btn btn-primary">Tampilkan</button></div>
</form>
<div class="report-area">
    <div class="mb-2">Periode: <?= $from; ?> s/d <?= $to; ?></div>
    <table class="table table-dark table-striped table-sm">
        <tbody>
            <tr><th colspan="2">Pendapatan</th></tr>
            <tr><td>Penjualan</td><td class="text-end"><?= format_rupiah($revenue); ?></td></tr>
            <tr><td>Pemasukan Lain</td><td class="text-end"><?= format_rupiah($otherIncome); ?></td></tr>
            <tr class="table-active fw-bold"><td>Total Pendapatan</td><td class="text-end"><?= format_rupiah($revenue + $otherIncome); ?></td></tr>

            <tr><th colspan="2" class="pt-3">Harga Pokok Penjualan</th></tr>
            <tr><td>COGS (berdasar harga beli)</td><td class="text-end"><?= format_rupiah($cogs); ?></td></tr>
            <tr class="table-active fw-bold"><td>Laba Kotor</td><td class="text-end"><?= format_rupiah($grossProfit); ?></td></tr>

            <tr><th colspan="2" class="pt-3">Beban Operasional</th></tr>
            <tr><td>Gaji</td><td class="text-end"><?= format_rupiah($salaryTotal); ?></td></tr>
            <tr><td>Operasional / Biaya lain</td><td class="text-end"><?= format_rupiah($opex); ?></td></tr>
            <tr class="table-active fw-bold"><td>Total Beban</td><td class="text-end"><?= format_rupiah($opex + $salaryTotal); ?></td></tr>

            <tr class="table-primary fw-bold"><td>Laba Bersih</td><td class="text-end"><?= format_rupiah($operatingProfit); ?></td></tr>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../layout/footer.php'; ?>
