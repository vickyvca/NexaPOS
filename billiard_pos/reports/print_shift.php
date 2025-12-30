<?php
require_once __DIR__ . '/../includes/functions.php';
check_login();

$start = $_GET['start'] ?? date('Y-m-d 06:00');
$end = $_GET['end'] ?? date('Y-m-d 23:59');
$shift_name = trim($_GET['shift_name'] ?? '');
$kasir_name = trim($_GET['kasir_name'] ?? ($_SESSION['user']['username'] ?? ''));
$racker_name = trim($_GET['racker_name'] ?? '');
$user_id = $_SESSION['user']['id'] ?? 0;

// pakai shift aktif jika ada dan parameter tidak di-set
$activeShift = $user_id ? get_active_shift($pdo, $user_id) : null;
if ($activeShift && empty($_GET['custom'])) {
    $start = $activeShift['start_time'];
    $end = $activeShift['end_time'] ?: date('Y-m-d H:i:s');
    if ($shift_name === '') $shift_name = $activeShift['shift_name'];
    if ($kasir_name === '' && !empty($_SESSION['user']['username'])) $kasir_name = $_SESSION['user']['username'];
}

// data order per operator (kasir) ini saja
$stmt = $pdo->prepare("SELECT o.*, t.name AS table_name, m.name AS member_name, u.username, s.start_time AS sess_start, s.end_time AS sess_end, s.total_minutes, s.package_id, p.name AS package_name FROM orders o LEFT JOIN billiard_tables t ON o.table_id = t.id LEFT JOIN members m ON o.member_id = m.id LEFT JOIN users u ON o.operator_id = u.id LEFT JOIN sessions s ON o.session_id = s.id LEFT JOIN packages p ON s.package_id = p.id WHERE o.is_paid = 1 AND o.order_time BETWEEN ? AND ? AND o.operator_id = ? ORDER BY o.order_time ASC");
$stmt->execute([$start, $end, $user_id]);
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

$runningStmt = $pdo->query("SELECT s.*, t.name AS table_name FROM sessions s JOIN billiard_tables t ON s.table_id = t.id WHERE s.status = 'running'");
$running = $runningStmt->fetchAll();

$totals = [
    'billing' => 0, 'pos' => 0, 'extra' => 0, 'discount' => 0, 'grand' => 0,
    'pay' => ['cash'=>0,'transfer'=>0,'qris'=>0,'other'=>0]
];
foreach ($orders as $o) {
    $billing = ($o['grand_total'] + $o['discount_amount']) - $o['subtotal'];
    $totals['billing'] += $billing;
    $totals['pos'] += $o['subtotal'];
    $totals['extra'] += $o['extra_charge_amount'] ?? 0;
    $totals['discount'] += $o['discount_amount'];
    $totals['grand'] += $o['grand_total'];
    $pm = strtolower($o['payment_method'] ?? 'other');
    if (!isset($totals['pay'][$pm])) $pm = 'other';
    $totals['pay'][$pm] += (int)$o['grand_total'];
}
$expStmt = $pdo->prepare("SELECT * FROM expenses WHERE operator_id = ? AND expense_time BETWEEN ? AND ? ORDER BY expense_time ASC");
$expStmt->execute([$user_id, $start, $end]);
$expenses = $expStmt->fetchAll();
$expenseTotal = array_sum(array_map(fn($e)=> (int)$e['amount'], $expenses));
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Print Rekap Shift</title>
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
            <h5 class="mb-0">Rekap Kas Shift</h5>
            <small>Periode: <?php echo date('d-m-Y H:i', strtotime($start)); ?> s/d <?php echo date('d-m-Y H:i', strtotime($end)); ?></small><br>
            <?php if ($shift_name): ?><small>Shift: <?php echo htmlspecialchars($shift_name); ?></small><br><?php endif; ?>
            <?php if ($kasir_name): ?><small>Kasir: <?php echo htmlspecialchars($kasir_name); ?></small><br><?php endif; ?>
            <?php if ($racker_name): ?><small>Racker: <?php echo htmlspecialchars($racker_name); ?></small><?php endif; ?>
        </div>
        <button class="btn btn-primary no-print" onclick="window.print()">Print / Save PDF</button>
    </div>

    <div class="row mb-3">
        <div class="col-md-3"><div class="border p-2"><div>Billing</div><div class="fw-bold"><?php echo format_rupiah($totals['billing']); ?></div></div></div>
        <div class="col-md-3"><div class="border p-2"><div>POS</div><div class="fw-bold"><?php echo format_rupiah($totals['pos']); ?></div></div></div>
        <div class="col-md-3"><div class="border p-2"><div>Extra</div><div class="fw-bold"><?php echo format_rupiah($totals['extra']); ?></div></div></div>
        <div class="col-md-3"><div class="border p-2"><div>Grand</div><div class="fw-bold"><?php echo format_rupiah($totals['grand']); ?></div></div></div>
    </div>
    <div class="row mb-3">
        <div class="col-md-3"><div class="border p-2"><div>Cash</div><div class="fw-bold"><?php echo format_rupiah($totals['pay']['cash']); ?></div></div></div>
        <div class="col-md-3"><div class="border p-2"><div>Transfer</div><div class="fw-bold"><?php echo format_rupiah($totals['pay']['transfer']); ?></div></div></div>
        <div class="col-md-3"><div class="border p-2"><div>QRIS</div><div class="fw-bold"><?php echo format_rupiah($totals['pay']['qris']); ?></div></div></div>
        <div class="col-md-3"><div class="border p-2"><div>Pengeluaran</div><div class="fw-bold"><?php echo format_rupiah($expenseTotal); ?></div></div></div>
    </div>

    <h6>Detail Transaksi</h6>
    <table class="table table-bordered table-sm">
        <thead class="table-light">
            <tr><th>#</th><th>Jam</th><th>Customer</th><th>Meja</th><th>Durasi</th><th>Paket</th><th>Billing</th><th>POS</th><th>Extra</th><th>Diskon</th><th>Grand</th><th>Bayar</th><th>Items</th></tr>
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
                    <td><?php echo format_datetime($o['sess_start'] ?: $o['order_time']); ?><br><small><?php echo $o['sess_end'] ? format_datetime($o['sess_end']) : '-'; ?></small></td>
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
        <thead class="table-light"><tr><th>#</th><th>Waktu</th><th>Kategori</th><th>Deskripsi</th><th>Nominal</th></tr></thead>
        <tbody>
            <?php $no=1; foreach ($expenses as $e): ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo format_datetime($e['expense_time']); ?></td>
                    <td><?php echo htmlspecialchars($e['category']); ?></td>
                    <td><?php echo htmlspecialchars($e['description']); ?></td>
                    <td><?php echo format_rupiah($e['amount']); ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$expenses): ?>
                <tr><td colspan="5" class="text-center text-muted">Tidak ada pengeluaran</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($running): ?>
        <h6 class="mt-4">Sesi Masih Berjalan</h6>
        <ul>
            <?php foreach ($running as $r): ?>
                <?php $mins = (int)floor((time() - strtotime($r['start_time'])) / 60); ?>
                <li><?php echo htmlspecialchars($r['table_name']); ?> - <?php echo human_duration($mins); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
</body>
</html>
