<?php
require_once __DIR__ . '/../includes/functions.php';
check_login('admin');

$start = $_GET['start'] ?? date('Y-m-01');
$end = $_GET['end'] ?? date('Y-m-d');

$salesStmt = $pdo->prepare("SELECT SUM(grand_total) AS sales, SUM(discount_amount) AS disc, SUM(subtotal) AS pos_sub FROM orders WHERE is_paid = 1 AND DATE(order_time) BETWEEN ? AND ?");
$salesStmt->execute([$start, $end]);
$sales = $salesStmt->fetch();

$purchaseStmt = $pdo->prepare("SELECT SUM(total) AS purchases FROM purchases WHERE DATE(purchase_time) BETWEEN ? AND ?");
$purchaseStmt->execute([$start, $end]);
$purchases = $purchaseStmt->fetch();

$expenseStmt = $pdo->prepare("SELECT SUM(amount) AS exp FROM expenses WHERE DATE(expense_time) BETWEEN ? AND ?");
$expenseStmt->execute([$start, $end]);
$expenses = $expenseStmt->fetch();

$total_sales = (int)($sales['sales'] ?? 0);
$total_disc = (int)($sales['disc'] ?? 0);
$total_pos_sub = (int)($sales['pos_sub'] ?? 0);
$total_purchase = (int)($purchases['purchases'] ?? 0);
$total_expense = (int)($expenses['exp'] ?? 0);
$gross_profit = $total_sales - $total_purchase;
$net_profit = $gross_profit - $total_expense;
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Laporan Laba Rugi</h4>
    <div class="d-flex gap-2">
        <a href="/billiard_pos/index.php" class="btn btn-outline-light btn-sm">Kembali</a>
        <a class="btn btn-success btn-sm" target="_blank" href="/billiard_pos/reports/print_profit.php?start=<?php echo htmlspecialchars($start); ?>&end=<?php echo htmlspecialchars($end); ?>">Export PDF / Print</a>
    </div>
</div>
<form class="row g-2 mb-3">
    <div class="col-auto">
        <label class="form-label text-muted small mb-0">Dari</label>
        <input type="date" name="start" class="form-control" value="<?php echo htmlspecialchars($start); ?>">
    </div>
    <div class="col-auto">
        <label class="form-label text-muted small mb-0">Sampai</label>
        <input type="date" name="end" class="form-control" value="<?php echo htmlspecialchars($end); ?>">
    </div>
    <div class="col-auto align-self-end">
        <button class="btn btn-primary">Filter</button>
    </div>
</form>

<div class="row mb-3">
    <div class="col-md-3"><div class="card bg-secondary text-light"><div class="card-body"><div>Penjualan (Grand)</div><div class="fs-4"><?php echo format_rupiah($total_sales); ?></div></div></div></div>
    <div class="col-md-3"><div class="card bg-secondary text-light"><div class="card-body"><div>Diskon</div><div class="fs-4"><?php echo format_rupiah($total_disc); ?></div></div></div></div>
    <div class="col-md-3"><div class="card bg-secondary text-light"><div class="card-body"><div>Pembelian</div><div class="fs-4"><?php echo format_rupiah($total_purchase); ?></div></div></div></div>
    <div class="col-md-3"><div class="card bg-secondary text-light"><div class="card-body"><div>Pengeluaran</div><div class="fs-4"><?php echo format_rupiah($total_expense); ?></div></div></div></div>
</div>
<div class="row mb-3">
    <div class="col-md-6"><div class="card bg-secondary text-light"><div class="card-body"><div>Laba Kotor</div><div class="fs-4"><?php echo format_rupiah($gross_profit); ?></div></div></div></div>
    <div class="col-md-6"><div class="card bg-secondary text-light"><div class="card-body"><div>Laba Bersih</div><div class="fs-4"><?php echo format_rupiah($net_profit); ?></div></div></div></div>
</div>

<div class="card bg-secondary text-light">
    <div class="card-header">Ringkasan</div>
    <div class="card-body">
        <ul class="list-group list-group-flush">
            <li class="list-group-item bg-secondary text-light d-flex justify-content-between"><span>Penjualan POS (subtotal)</span><span><?php echo format_rupiah($total_pos_sub); ?></span></li>
            <li class="list-group-item bg-secondary text-light d-flex justify-content-between"><span>Diskon</span><span><?php echo format_rupiah($total_disc); ?></span></li>
            <li class="list-group-item bg-secondary text-light d-flex justify-content-between"><span>Pembelian</span><span><?php echo format_rupiah($total_purchase); ?></span></li>
            <li class="list-group-item bg-secondary text-light d-flex justify-content-between"><span>Pengeluaran Operasional</span><span><?php echo format_rupiah($total_expense); ?></span></li>
            <li class="list-group-item bg-secondary text-light d-flex justify-content-between fw-bold"><span>Laba Kotor</span><span><?php echo format_rupiah($gross_profit); ?></span></li>
            <li class="list-group-item bg-secondary text-light d-flex justify-content-between fw-bold"><span>Laba Bersih</span><span><?php echo format_rupiah($net_profit); ?></span></li>
        </ul>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
