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
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Print Laba Rugi</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body { background:#fff; color:#000; }
        .table td, .table th { padding:6px; }
        @media print { .no-print { display:none; } }
    </style>
</head>
<body>
<div class="container-fluid my-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
            <h5 class="mb-0">Laporan Laba Rugi</h5>
            <small><?php echo htmlspecialchars($start); ?> s/d <?php echo htmlspecialchars($end); ?></small>
        </div>
        <button class="btn btn-primary no-print" onclick="window.print()">Print / Save PDF</button>
    </div>
    <div class="row mb-3">
        <div class="col-md-3"><div class="border p-2"><div>Penjualan (Grand)</div><div class="fw-bold"><?php echo format_rupiah($total_sales); ?></div></div></div>
        <div class="col-md-3"><div class="border p-2"><div>Diskon</div><div class="fw-bold"><?php echo format_rupiah($total_disc); ?></div></div></div>
        <div class="col-md-3"><div class="border p-2"><div>Pembelian</div><div class="fw-bold"><?php echo format_rupiah($total_purchase); ?></div></div></div>
        <div class="col-md-3"><div class="border p-2"><div>Pengeluaran</div><div class="fw-bold"><?php echo format_rupiah($total_expense); ?></div></div></div>
    </div>
    <div class="row mb-3">
        <div class="col-md-6"><div class="border p-2"><div>Laba Kotor</div><div class="fw-bold"><?php echo format_rupiah($gross_profit); ?></div></div></div>
        <div class="col-md-6"><div class="border p-2"><div>Laba Bersih</div><div class="fw-bold"><?php echo format_rupiah($net_profit); ?></div></div></div>
    </div>
    <div class="card">
        <div class="card-header">Ringkasan</div>
        <div class="card-body p-0">
            <table class="table table-bordered table-sm mb-0">
                <tr><th>Penjualan POS (Subtotal)</th><td><?php echo format_rupiah($total_pos_sub); ?></td></tr>
                <tr><th>Diskon</th><td><?php echo format_rupiah($total_disc); ?></td></tr>
                <tr><th>Pembelian</th><td><?php echo format_rupiah($total_purchase); ?></td></tr>
                <tr><th>Pengeluaran Operasional</th><td><?php echo format_rupiah($total_expense); ?></td></tr>
                <tr><th>Laba Kotor</th><td><?php echo format_rupiah($gross_profit); ?></td></tr>
                <tr><th>Laba Bersih</th><td><?php echo format_rupiah($net_profit); ?></td></tr>
            </table>
        </div>
    </div>
</div>
</body>
</html>
