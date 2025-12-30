<?php
require_once __DIR__ . '/../includes/functions.php';
check_login('admin');

$start = $_GET['start'] ?? date('Y-m-01');
$end = $_GET['end'] ?? date('Y-m-d');

$stmt = $pdo->prepare("SELECT o.*, u.username, t.name AS table_name, m.name AS member_name, s.start_time, s.end_time, s.total_minutes, s.package_id, p.name AS package_name FROM orders o LEFT JOIN users u ON o.operator_id = u.id LEFT JOIN billiard_tables t ON o.table_id = t.id LEFT JOIN members m ON o.member_id = m.id LEFT JOIN sessions s ON o.session_id = s.id LEFT JOIN packages p ON s.package_id = p.id WHERE DATE(o.order_time) BETWEEN ? AND ? AND o.is_paid = 1 ORDER BY o.order_time DESC");
$stmt->execute([$start, $end]);
$orders = $stmt->fetchAll();

$orderItemsMap = [];
if ($orders) {
    $ids = array_column($orders, 'id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $oiStmt = $pdo->prepare("SELECT oi.*, pr.name FROM order_items oi LEFT JOIN products pr ON oi.product_id = pr.id WHERE oi.order_id IN ($in)");
    $oiStmt->execute($ids);
    foreach ($oiStmt as $oi) {
        $orderItemsMap[$oi['order_id']][] = $oi;
    }
}

$expenseStmt = $pdo->prepare("SELECT e.*, a.name AS account_name, u.username FROM expenses e JOIN accounts a ON e.account_id = a.id LEFT JOIN users u ON e.operator_id = u.id WHERE DATE(e.expense_time) BETWEEN ? AND ? ORDER BY e.expense_time DESC");
$expenseStmt->execute([$start, $end]);
$expenses = $expenseStmt->fetchAll();
$total_expense = array_sum(array_map(fn($e) => (int)$e['amount'], $expenses));

$total_billiard = $total_pos = $grand = 0;
$payBreak = ['cash'=>0,'transfer'=>0,'qris'=>0,'other'=>0];
foreach ($orders as $o) {
    $billiard = ($o['grand_total'] + $o['discount_amount']) - $o['subtotal'];
    $total_billiard += $billiard;
    $total_pos += $o['subtotal'];
    $grand += $o['grand_total'];
    $pm = strtolower($o['payment_method'] ?? 'other');
    if (!isset($payBreak[$pm])) $pm = 'other';
    $payBreak[$pm] += (int)$o['grand_total'];
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Print Laporan Range</title>
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
            <h5 class="mb-0">Laporan Range</h5>
            <small><?php echo htmlspecialchars($start); ?> s/d <?php echo htmlspecialchars($end); ?></small><br>
            <small>Total transaksi: <?php echo count($orders); ?></small>
        </div>
        <button class="btn btn-primary no-print" onclick="window.print()">Print / Save PDF</button>
    </div>
    <div class="row mb-3">
        <div class="col-md-3"><div class="border p-2"><div>Total Billing</div><div class="fw-bold"><?php echo format_rupiah($total_billiard); ?></div></div></div>
        <div class="col-md-3"><div class="border p-2"><div>Total POS</div><div class="fw-bold"><?php echo format_rupiah($total_pos); ?></div></div></div>
        <div class="col-md-3"><div class="border p-2"><div>Grand</div><div class="fw-bold"><?php echo format_rupiah($grand); ?></div></div></div>
        <div class="col-md-3"><div class="border p-2"><div>Pengeluaran</div><div class="fw-bold"><?php echo format_rupiah($total_expense); ?></div></div></div>
    </div>
    <div class="row mb-3">
        <div class="col-md-4"><div class="border p-2"><div>Cash</div><div class="fw-bold"><?php echo format_rupiah($payBreak['cash']); ?></div></div></div>
        <div class="col-md-4"><div class="border p-2"><div>Transfer</div><div class="fw-bold"><?php echo format_rupiah($payBreak['transfer']); ?></div></div></div>
        <div class="col-md-4"><div class="border p-2"><div>QRIS</div><div class="fw-bold"><?php echo format_rupiah($payBreak['qris']); ?></div></div></div>
    </div>

    <table class="table table-bordered table-sm">
        <thead class="table-light">
            <tr>
                <th>#</th><th>Jam</th><th>Customer</th><th>Meja</th><th>Durasi</th><th>Paket</th><th>Billing</th><th>POS</th><th>Extra</th><th>Diskon</th><th>Grand</th><th>Bayar</th><th>Items</th>
            </tr>
        </thead>
        <tbody>
            <?php $no=1; foreach ($orders as $o): ?>
                <?php
                    $billing = ($o['grand_total'] + $o['discount_amount']) - $o['subtotal'];
                    $items = $orderItemsMap[$o['id']] ?? [];
                    $itemStr = [];
                    foreach ($items as $it) { $itemStr[] = ($it['name'] ?: 'Item')." x{$it['qty']} (".format_rupiah($it['subtotal']).")"; }
                ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo format_datetime($o['start_time'] ?: $o['order_time']); ?><br><small><?php echo $o['end_time'] ? format_datetime($o['end_time']) : '-'; ?></small></td>
                    <td><?php echo htmlspecialchars($o['customer_name'] ?? '-'); ?><br><small><?php echo htmlspecialchars($o['member_name'] ?? '-'); ?></small></td>
                    <td><?php echo htmlspecialchars($o['table_name']); ?><br><small><?php echo htmlspecialchars($o['username']); ?></small></td>
                    <td><?php echo $o['total_minutes'] ? human_duration($o['total_minutes']) : '-'; ?></td>
                    <td><?php echo $o['package_name'] ? htmlspecialchars($o['package_name']) : '-'; ?></td>
                    <td><?php echo format_rupiah($billing); ?></td>
                    <td><?php echo format_rupiah($o['subtotal']); ?></td>
                    <td><?php echo format_rupiah($o['extra_charge_amount'] ?? 0); ?></td>
                    <td><?php echo format_rupiah($o['discount_amount']); ?></td>
                    <td><?php echo format_rupiah($o['grand_total']); ?></td>
                    <td><?php echo format_rupiah($o['payment_amount']); ?><br><small><?php echo strtoupper($o['payment_method']); ?></small></td>
                    <td class="small"><?php echo $itemStr ? htmlspecialchars(implode(' | ', $itemStr)) : '-'; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h6 class="mt-4">Pengeluaran</h6>
    <table class="table table-bordered table-sm">
        <thead class="table-light"><tr><th>#</th><th>Waktu</th><th>Akun</th><th>Kategori</th><th>Deskripsi</th><th>Nominal</th><th>Operator</th></tr></thead>
        <tbody>
            <?php $no=1; foreach ($expenses as $e): ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo format_datetime($e['expense_time']); ?></td>
                    <td><?php echo htmlspecialchars($e['account_name']); ?></td>
                    <td><?php echo htmlspecialchars($e['category']); ?></td>
                    <td><?php echo htmlspecialchars($e['description']); ?></td>
                    <td><?php echo format_rupiah($e['amount']); ?></td>
                    <td><?php echo htmlspecialchars($e['username']); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$expenses): ?>
                <tr><td colspan="7" class="text-center text-muted">Tidak ada pengeluaran</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
