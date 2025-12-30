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

$maintStmt = $pdo->prepare("SELECT ml.*, t.name AS table_name, u.username FROM maintenance_logs ml JOIN billiard_tables t ON ml.table_id = t.id LEFT JOIN users u ON ml.operator_id = u.id WHERE DATE(ml.start_time) BETWEEN ? AND ? ORDER BY ml.start_time DESC");
$maintStmt->execute([$start, $end]);
$maintLogs = $maintStmt->fetchAll();

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
$journalBreak = [];
$jstmt = $pdo->prepare("SELECT a.type, SUM(CASE WHEN j.type='in' THEN j.amount ELSE 0 END) AS masuk, SUM(CASE WHEN j.type='out' THEN j.amount ELSE 0 END) AS keluar FROM journals j JOIN accounts a ON j.account_id = a.id WHERE DATE(j.txn_time) BETWEEN ? AND ? GROUP BY a.type");
$jstmt->execute([$start, $end]);
foreach ($jstmt as $j) {
    $journalBreak[$j['type']] = ['in' => (int)$j['masuk'], 'out' => (int)$j['keluar']];
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="laporan-range-' . $start . '-sd-' . $end . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Jam Mulai', 'Jam Selesai', 'Customer', 'Member', 'Meja', 'Durasi', 'Paket', 'Billing', 'POS', 'Extra', 'Diskon', 'Grand', 'Bayar', 'Metode', 'POS Items']);
    foreach ($orders as $o) {
        $items = $orderItemsMap[$o['id']] ?? [];
        $itemStrParts = [];
        foreach ($items as $it) {
            $itemStrParts[] = ($it['name'] ?: 'Item '.$it['product_id']) . ' x' . $it['qty'] . ' (' . $it['subtotal'] . ')';
        }
        fputcsv($out, [
            $o['start_time'] ?: $o['order_time'],
            $o['end_time'] ?: '',
            $o['customer_name'],
            $o['member_name'],
            $o['table_name'],
            $o['total_minutes'],
            $o['package_name'],
            ($o['grand_total'] + $o['discount_amount']) - $o['subtotal'],
            $o['subtotal'],
            $o['extra_charge_amount'],
            $o['discount_amount'],
            $o['grand_total'],
            $o['payment_amount'],
            $o['payment_method'],
            implode(' | ', $itemStrParts)
        ]);
    }
    fclose($out);
    exit;
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Laporan Range</h4>
    <div class="d-flex gap-2">
        <a href="/billiard_pos/index.php" class="btn btn-outline-light btn-sm">Kembali</a>
        <a class="btn btn-success btn-sm" target="_blank" href="/billiard_pos/reports/print_range.php?start=<?php echo htmlspecialchars($start); ?>&end=<?php echo htmlspecialchars($end); ?>">Export PDF / Print</a>
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
    <div class="col-md-4">
        <div class="card bg-secondary text-light"><div class="card-body"><div>Total Billing</div><div class="fs-4"><?php echo format_rupiah($total_billiard); ?></div></div></div>
    </div>
    <div class="col-md-4">
        <div class="card bg-secondary text-light"><div class="card-body"><div>Total POS</div><div class="fs-4"><?php echo format_rupiah($total_pos); ?></div></div></div>
    </div>
    <div class="col-md-4">
        <div class="card bg-secondary text-light"><div class="card-body"><div>Grand Total</div><div class="fs-4"><?php echo format_rupiah($grand); ?></div></div></div>
    </div>
</div>
<div class="row mb-3">
    <div class="col-md-4">
        <div class="card bg-secondary text-light"><div class="card-body"><div>Cash</div><div class="fs-5"><?php echo format_rupiah($payBreak['cash']); ?></div></div></div>
    </div>
    <div class="col-md-4">
        <div class="card bg-secondary text-light"><div class="card-body"><div>Transfer</div><div class="fs-5"><?php echo format_rupiah($payBreak['transfer']); ?></div></div></div>
    </div>
    <div class="col-md-4">
        <div class="card bg-secondary text-light"><div class="card-body"><div>QRIS</div><div class="fs-5"><?php echo format_rupiah($payBreak['qris']); ?></div></div></div>
    </div>
</div>
<div class="row mb-3">
    <div class="col-md-4">
        <div class="card bg-secondary text-light"><div class="card-body"><div>Total Pengeluaran</div><div class="fs-4"><?php echo format_rupiah($total_expense); ?></div></div></div>
    </div>
</div>
<div class="row mb-3">
    <?php foreach (['cash' => 'Kas', 'bank' => 'Bank/Transfer', 'qris' => 'QRIS'] as $t => $label): ?>
        <?php $in = $journalBreak[$t]['in'] ?? 0; $out = $journalBreak[$t]['out'] ?? 0; ?>
        <div class="col-md-4">
            <div class="card bg-secondary text-light">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><?php echo $label; ?></span>
                        <span class="badge bg-dark">Jurnal</span>
                    </div>
                    <div class="small text-muted">Masuk: <?php echo format_rupiah($in); ?></div>
                    <div class="small text-muted">Keluar: <?php echo format_rupiah($out); ?></div>
                    <div class="fs-5 fw-bold">Saldo: <?php echo format_rupiah($in - $out); ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card bg-secondary text-light">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Detail Transaksi</span>
        <a class="btn btn-sm btn-outline-light" href="?start=<?php echo htmlspecialchars($start); ?>&end=<?php echo htmlspecialchars($end); ?>&export=csv">Export CSV</a>
    </div>
    <div class="card-body">
        <table class="table table-dark table-striped data-table">
            <thead><tr><th>#</th><th>Jam (Start-End)</th><th>Customer</th><th>Meja</th><th>Durasi</th><th>Paket</th><th>Billing</th><th>POS</th><th>Extra</th><th>Diskon</th><th>Grand</th><th>Bayar</th><th>POS Detail</th></tr></thead>
            <tbody>
            <?php $no=1; foreach ($orders as $o): ?>
                <?php
                $billing = ($o['grand_total'] + $o['discount_amount']) - $o['subtotal'];
                $dur = $o['total_minutes'] ? human_duration($o['total_minutes']) : '-';
                $startLabel = $o['start_time'] ? format_datetime($o['start_time']) : format_datetime($o['order_time']);
                $endLabel = $o['end_time'] ? format_datetime($o['end_time']) : '-';
                $items = $orderItemsMap[$o['id']] ?? [];
                ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo $startLabel; ?><br><small><?php echo $endLabel; ?></small></td>
                    <td><?php echo htmlspecialchars($o['customer_name'] ?? '-'); ?><br><small><?php echo htmlspecialchars($o['member_name'] ?? '-'); ?></small></td>
                    <td><?php echo htmlspecialchars($o['table_name']); ?><br><small><?php echo htmlspecialchars($o['username']); ?></small></td>
                    <td><?php echo $dur; ?></td>
                    <td><?php echo $o['package_name'] ? htmlspecialchars($o['package_name']) : '-'; ?></td>
                    <td><?php echo format_rupiah($billing); ?></td>
                    <td><?php echo format_rupiah($o['subtotal']); ?></td>
                    <td><?php echo format_rupiah($o['extra_charge_amount'] ?? 0); ?></td>
                    <td><?php echo format_rupiah($o['discount_amount']); ?></td>
                    <td><?php echo format_rupiah($o['grand_total']); ?></td>
                    <td><?php echo strtoupper($o['payment_method']); ?></td>
                    <td>
                        <?php if ($items): ?>
                            <details>
                                <summary>Lihat</summary>
                                <ul class="mb-0 small">
                                    <?php foreach ($items as $it): ?>
                                        <li><?php echo htmlspecialchars($it['name'] ?? ('Item #'.$it['product_id'])); ?> x <?php echo $it['qty']; ?> (<?php echo format_rupiah($it['subtotal']); ?>)</li>
                                    <?php endforeach; ?>
                                </ul>
                            </details>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card bg-secondary text-light mt-3">
    <div class="card-header">List Transaksi (Ringkas)</div>
    <div class="card-body">
        <table class="table table-dark table-striped data-table">
            <thead><tr><th>#</th><th>Meja</th><th>Mulai</th><th>Selesai</th><th>Customer</th><th>Paket</th><th>Belanja</th><th>Total</th></tr></thead>
            <tbody>
            <?php $no=1; foreach ($orders as $o): ?>
                <?php
                    $items = $orderItemsMap[$o['id']] ?? [];
                    $itemStr = [];
                    foreach ($items as $it) { $itemStr[] = ($it['name'] ?: 'Item #'.$it['product_id']).' x'.$it['qty']; }
                ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($o['table_name']); ?></td>
                    <td><?php echo $o['start_time'] ? format_datetime($o['start_time']) : format_datetime($o['order_time']); ?></td>
                    <td><?php echo $o['end_time'] ? format_datetime($o['end_time']) : '-'; ?></td>
                    <td><?php echo htmlspecialchars($o['customer_name'] ?? '-'); ?></td>
                    <td><?php echo $o['package_name'] ? htmlspecialchars($o['package_name']) : '-'; ?></td>
                    <td><?php echo $itemStr ? htmlspecialchars(implode(', ', $itemStr)) : '-'; ?></td>
                    <td><?php echo format_rupiah($o['grand_total']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card bg-secondary text-light mt-3">
    <div class="card-header">Pengeluaran</div>
    <div class="card-body">
        <table class="table table-dark table-striped data-table">
            <thead><tr><th>#</th><th>Waktu</th><th>Akun</th><th>Kategori</th><th>Deskripsi</th><th>Nominal</th><th>Operator</th></tr></thead>
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
</div>

<div class="card bg-secondary text-light mt-3">
    <div class="card-header">Log Maintenance</div>
    <div class="card-body">
        <table class="table table-dark table-striped data-table">
            <thead><tr><th>#</th><th>Mulai</th><th>Selesai</th><th>Meja</th><th>Operator</th><th>Durasi (mnt)</th></tr></thead>
            <tbody>
            <?php $no=1; foreach ($maintLogs as $m): ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo format_datetime($m['start_time']); ?></td>
                    <td><?php echo $m['end_time'] ? format_datetime($m['end_time']) : '-'; ?></td>
                    <td><?php echo htmlspecialchars($m['table_name']); ?></td>
                    <td><?php echo htmlspecialchars($m['username']); ?></td>
                    <td><?php echo $m['duration_minutes']; ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$maintLogs): ?>
                <tr><td colspan="6" class="text-center text-muted">Tidak ada maintenance</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
