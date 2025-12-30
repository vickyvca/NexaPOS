<?php
require_once __DIR__ . '/../includes/functions.php';
check_login(); // kasir dan admin bisa rekap shift

$start = $_GET['start'] ?? date('Y-m-d 06:00');
$end = $_GET['end'] ?? date('Y-m-d 23:59');
$custom_range = isset($_GET['custom']);
$send = isset($_GET['send']);
$shift_name = trim($_GET['shift_name'] ?? '');
$kasir_name = trim($_GET['kasir_name'] ?? '');
$racker_name = trim($_GET['racker_name'] ?? '');
$current_user = $_SESSION['user']['username'] ?? '';
$user_id = $_SESSION['user']['id'] ?? null;
// jika ada shift aktif dan user tidak pilih custom, pakai rentang shift aktif
$activeShift = ($user_id) ? get_active_shift($pdo, $user_id) : null;
if ($activeShift && !$custom_range) {
    $start = $activeShift['start_time'];
    $end = $activeShift['end_time'] ?: date('Y-m-d H:i:s');
    if ($shift_name === '') $shift_name = $activeShift['shift_name'];
}
if ($kasir_name === '' && $current_user) {
    $kasir_name = $current_user;
}

$userFilterId = $_SESSION['user']['id'] ?? 0;
$stmt = $pdo->prepare("SELECT o.*, t.name AS table_name, m.name AS member_name, u.username, s.start_time AS sess_start, s.end_time AS sess_end FROM orders o LEFT JOIN billiard_tables t ON o.table_id = t.id LEFT JOIN members m ON o.member_id = m.id LEFT JOIN users u ON o.operator_id = u.id LEFT JOIN sessions s ON o.session_id = s.id WHERE o.is_paid = 1 AND o.order_time BETWEEN ? AND ? AND o.operator_id = ? ORDER BY o.order_time ASC");
$stmt->execute([$start, $end, $userFilterId]);
$orders = $stmt->fetchAll();

$runningStmt = $pdo->query("SELECT s.*, t.name AS table_name, tr.rate_per_hour, tr.min_minutes, p.name AS package_name, p.duration_minutes, p.special_price FROM sessions s JOIN billiard_tables t ON s.table_id = t.id JOIN tariffs tr ON s.tariff_id = tr.id LEFT JOIN packages p ON s.package_id = p.id WHERE s.status = 'running'");
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
$expenseTotal = 0;
$expStmt = $pdo->prepare("SELECT SUM(amount) FROM expenses WHERE operator_id = ? AND expense_time BETWEEN ? AND ?");
$expStmt->execute([$userFilterId, $start, $end]);
$expenseTotal = (int)($expStmt->fetchColumn() ?: 0);

$sendResult = '';
if ($send) {
    $settings = load_company_settings();
    if (!empty($settings['fonnte_target'])) {
        $periodLabel = date('d-m-Y H:i', strtotime($start)) . " s/d " . date('d-m-Y H:i', strtotime($end));
        $runningMsg = '';
        if ($running) {
            $parts = [];
            foreach ($running as $r) {
                $mins = (int)floor((time() - strtotime($r['start_time'])) / 60);
                $parts[] = $r['table_name'] . ' (' . human_duration($mins) . ')';
            }
            $runningMsg = "\nAktif: " . implode(', ', $parts);
        }
        $msg = "Rekap Shift\n" .
               ($shift_name ? "Shift: {$shift_name}\n" : '') .
               ($kasir_name ? "Kasir: {$kasir_name}\n" : '') .
               ($racker_name ? "Racker: {$racker_name}\n" : '') .
               "Periode: {$periodLabel}\n" .
               "*Billing*: " . format_rupiah($totals['billing']) . "\n" .
               "*POS*: " . format_rupiah($totals['pos']) . "\n" .
               "*Extra*: " . format_rupiah($totals['extra']) . "\n" .
               "*Diskon*: " . format_rupiah($totals['discount']) . "\n" .
               "*Grand*: " . format_rupiah($totals['grand']) . "\n" .
               "Cash: " . format_rupiah($totals['pay']['cash']) . "\n" .
               "Transfer: " . format_rupiah($totals['pay']['transfer']) . "\n" .
               "QRIS: " . format_rupiah($totals['pay']['qris']) .
               $runningMsg;
        $sendResult = send_fonnte_notification($settings['fonnte_target'], $msg);
    } else {
        $sendResult = 'Token/target belum diset.';
    }
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Rekap Kas Shift</h4>
    <div class="d-flex gap-2">
        <a href="/billiard_pos/index.php" class="btn btn-outline-light btn-sm">Kembali</a>
        <a class="btn btn-success btn-sm" target="_blank" href="/billiard_pos/reports/print_shift.php?start=<?php echo urlencode($start); ?>&end=<?php echo urlencode($end); ?>&shift_name=<?php echo urlencode($shift_name); ?>&kasir_name=<?php echo urlencode($kasir_name); ?>&racker_name=<?php echo urlencode($racker_name); ?>&custom=1">Export PDF / Print</a>
    </div>
</div>
<?php if ($shift_name || $kasir_name || $racker_name): ?>
<div class="alert alert-dark py-2">
    <?php if ($shift_name): ?>Shift: <strong><?php echo htmlspecialchars($shift_name); ?></strong> &nbsp;<?php endif; ?>
    <?php if ($kasir_name): ?>Kasir: <strong><?php echo htmlspecialchars($kasir_name); ?></strong> &nbsp;<?php endif; ?>
    <?php if ($racker_name): ?>Racker: <strong><?php echo htmlspecialchars($racker_name); ?></strong><?php endif; ?>
    <?php if ($activeShift && !$custom_range): ?> <span class="badge bg-info text-dark">Mengikuti shift aktif</span><?php endif; ?>
</div>
<?php endif; ?>
<?php if ($send): ?>
    <div class="alert alert-info py-2">Notifikasi dikirim: <?php echo htmlspecialchars($sendResult ?: 'terkirim'); ?></div>
<?php endif; ?>
<div class="mb-2">
    <button class="btn btn-outline-light btn-sm" onclick="window.print()">Print Rekap Kas</button>
</div>
<form class="row g-2 mb-3">
    <div class="col-md-4">
        <label class="form-label text-info small mb-0">Mulai</label>
        <input type="datetime-local" name="start" class="form-control" value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($start))); ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label text-info small mb-0">Selesai</label>
        <input type="datetime-local" name="end" class="form-control" value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($end))); ?>">
    </div>
    <div class="col-md-4 align-self-end d-flex gap-2">
        <input type="hidden" name="custom" value="1">
        <button class="btn btn-primary">Terapkan</button>
        <button class="btn btn-success" name="send" value="1">Kirim Rekap via WA</button>
    </div>
    <div class="col-md-4">
        <label class="form-label text-info small mb-0">Nama Shift</label>
        <input type="text" name="shift_name" class="form-control" value="<?php echo htmlspecialchars($shift_name); ?>" placeholder="Shift 1 / Shift Siang">
    </div>
    <div class="col-md-4">
        <label class="form-label text-info small mb-0">Kasir</label>
        <input type="text" name="kasir_name" class="form-control" value="<?php echo htmlspecialchars($kasir_name); ?>" placeholder="Nama kasir">
    </div>
    <div class="col-md-4">
        <label class="form-label text-info small mb-0">Racker</label>
        <input type="text" name="racker_name" class="form-control" value="<?php echo htmlspecialchars($racker_name); ?>" placeholder="Nama racker">
    </div>
</form>

<div class="row mb-3">
    <div class="col-md-3"><div class="card bg-secondary text-light"><div class="card-body"><div>Billing</div><div class="fs-5"><?php echo format_rupiah($totals['billing']); ?></div></div></div></div>
    <div class="col-md-3"><div class="card bg-secondary text-light"><div class="card-body"><div>POS</div><div class="fs-5"><?php echo format_rupiah($totals['pos']); ?></div></div></div></div>
    <div class="col-md-3"><div class="card bg-secondary text-light"><div class="card-body"><div>Extra Charge</div><div class="fs-5"><?php echo format_rupiah($totals['extra']); ?></div></div></div></div>
    <div class="col-md-3"><div class="card bg-secondary text-light"><div class="card-body"><div>Grand Total</div><div class="fs-5"><?php echo format_rupiah($totals['grand']); ?></div></div></div></div>
</div>
<div class="row mb-3">
    <div class="col-md-4"><div class="card bg-secondary text-light"><div class="card-body"><div>Cash</div><div class="fs-6"><?php echo format_rupiah($totals['pay']['cash']); ?></div></div></div></div>
    <div class="col-md-4"><div class="card bg-secondary text-light"><div class="card-body"><div>Transfer</div><div class="fs-6"><?php echo format_rupiah($totals['pay']['transfer']); ?></div></div></div></div>
    <div class="col-md-4"><div class="card bg-secondary text-light"><div class="card-body"><div>QRIS</div><div class="fs-6"><?php echo format_rupiah($totals['pay']['qris']); ?></div></div></div></div>
</div>

<div class="card bg-secondary text-light mb-3" id="rekapPrint">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Rekap Kas (Print)</span>
        <button class="btn btn-sm btn-outline-light" onclick="window.print()">Print</button>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4">
                <div class="fw-bold">Billing</div>
                <div class="fs-6"><?php echo format_rupiah($totals['billing']); ?></div>
                <div class="fw-bold mt-2">POS</div>
                <div class="fs-6"><?php echo format_rupiah($totals['pos']); ?></div>
                <div class="fw-bold mt-2">Sub Total</div>
                <div class="fs-5"><?php echo format_rupiah($totals['billing'] + $totals['pos']); ?></div>
            </div>
            <div class="col-md-4">
                <div class="fw-bold">Pengeluaran</div>
                <div class="fs-6"><?php echo format_rupiah($expenseTotal); ?></div>
                <div class="fw-bold mt-2">Total Setelah Pengeluaran</div>
                <div class="fs-5"><?php echo format_rupiah(($totals['billing'] + $totals['pos']) - $expenseTotal); ?></div>
            </div>
            <div class="col-md-4">
                <div class="fw-bold">Rekap Pembayaran</div>
                <div class="small">Tunai: <?php echo format_rupiah($totals['pay']['cash']); ?></div>
                <div class="small">Card/Transfer: <?php echo format_rupiah($totals['pay']['transfer']); ?></div>
                <div class="small">QRIS: <?php echo format_rupiah($totals['pay']['qris']); ?></div>
                <div class="fw-bold mt-2">Total</div>
                <div class="fs-5"><?php echo format_rupiah($totals['pay']['cash'] + $totals['pay']['transfer'] + $totals['pay']['qris']); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Receipt-style print (80mm) -->
<div id="receipt" class="d-none d-print-block">
    <div class="text-center section">
        <div class="title">Rekap Kas Shift</div>
        <div><?php echo htmlspecialchars($shift_name ?: 'Shift'); ?></div>
        <div><?php echo htmlspecialchars($kasir_name ?: ($_SESSION['user']['username'] ?? '')); ?></div>
        <div><?php echo date('d-m-Y H:i', strtotime($start)); ?> - <?php echo date('d-m-Y H:i', strtotime($end)); ?></div>
    </div>
    <div class="section">
        <div class="receipt-row"><span>Billing</span><span><?php echo format_rupiah($totals['billing']); ?></span></div>
        <div class="receipt-row"><span>POS</span><span><?php echo format_rupiah($totals['pos']); ?></span></div>
        <div class="receipt-row"><span>Sub Total</span><span><?php echo format_rupiah($totals['billing'] + $totals['pos']); ?></span></div>
        <div class="receipt-row"><span>Extra</span><span><?php echo format_rupiah($totals['extra']); ?></span></div>
        <div class="receipt-row"><span>Diskon</span><span><?php echo format_rupiah($totals['discount']); ?></span></div>
        <div class="receipt-row"><span>Pengeluaran</span><span><?php echo format_rupiah($expenseTotal); ?></span></div>
        <div class="receipt-row bold"><span>Grand</span><span><?php echo format_rupiah($totals['grand']); ?></span></div>
        <div class="receipt-row bold"><span>Total Setelah Pengeluaran</span><span><?php echo format_rupiah(($totals['billing'] + $totals['pos']) - $expenseTotal); ?></span></div>
    </div>
    <div class="section">
        <div class="receipt-row"><span>Tunai</span><span><?php echo format_rupiah($totals['pay']['cash']); ?></span></div>
        <div class="receipt-row"><span>Card/Transfer</span><span><?php echo format_rupiah($totals['pay']['transfer']); ?></span></div>
        <div class="receipt-row"><span>QRIS</span><span><?php echo format_rupiah($totals['pay']['qris']); ?></span></div>
        <div class="receipt-row bold"><span>Total Pembayaran</span><span><?php echo format_rupiah($totals['pay']['cash'] + $totals['pay']['transfer'] + $totals['pay']['qris']); ?></span></div>
    </div>
    <div class="section text-center">
        <div class="small">Terima kasih</div>
    </div>
</div>

<?php if ($running): ?>
<div class="card bg-secondary text-light mb-3">
    <div class="card-header">Meja Aktif / Pending</div>
    <div class="card-body">
        <table class="table table-dark table-striped">
            <thead><tr><th>Meja</th><th>Customer</th><th>Mulai</th><th>Durasi</th><th>Paket</th></tr></thead>
            <tbody>
            <?php foreach ($running as $r): ?>
                <?php $mins = (int)floor((time() - strtotime($r['start_time'])) / 60); ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['table_name']); ?></td>
                    <td><?php echo htmlspecialchars($r['customer_name']); ?></td>
                    <td><?php echo format_datetime($r['start_time']); ?></td>
                    <td><?php echo human_duration($mins); ?></td>
                    <td><?php echo $r['package_name'] ? htmlspecialchars($r['package_name']) : '-'; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card bg-secondary text-light">
    <div class="card-header">Detail Penjualan</div>
    <div class="card-body">
        <table class="table table-dark table-striped">
            <thead><tr><th>Waktu</th><th>Meja</th><th>Customer</th><th>Kasir</th><th>Metode</th><th>Billing</th><th>POS</th><th>Extra</th><th>Grand</th><th>Posisi Shift</th></tr></thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
                <?php $billing = ($o['grand_total'] + $o['discount_amount']) - $o['subtotal']; ?>
                <?php
                    $startTime = $o['sess_start'] ?? $o['order_time'];
                    $endTime = $o['sess_end'] ?? $o['order_time'];
                    $startIn = $startTime && $startTime >= $start && $startTime <= $end;
                    $endIn = $endTime && $endTime >= $start && $endTime <= $end;
                    $posLabels = [];
                    if ($startIn) $posLabels[] = 'Start di shift';
                    else if ($startTime && $startTime < $start) $posLabels[] = 'Start sebelum';
                    if ($endIn) $posLabels[] = 'Selesai di shift';
                    else if ($endTime && $endTime > $end) $posLabels[] = 'Selesai sesudah';
                    if (!$posLabels) $posLabels[] = '-';
                ?>
                <tr>
                    <td><?php echo format_datetime($o['order_time']); ?></td>
                    <td><?php echo htmlspecialchars($o['table_name'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($o['customer_name'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($o['username'] ?? '-'); ?></td>
                    <td><?php echo strtoupper($o['payment_method']); ?></td>
                    <td><?php echo format_rupiah($billing); ?></td>
                    <td><?php echo format_rupiah($o['subtotal']); ?></td>
                    <td><?php echo format_rupiah($o['extra_charge_amount'] ?? 0); ?></td>
                    <td><?php echo format_rupiah($o['grand_total']); ?></td>
                    <td class="small"><?php echo implode(', ', $posLabels); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
