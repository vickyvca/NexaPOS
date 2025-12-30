<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/lamp_control.php';
check_login();
$company = load_company_settings();

$table_id = (int)($_GET['table_id'] ?? 0);

$cartKey = $table_id;
$cart = $_SESSION['cart'][$cartKey] ?? [];
$tableStmt = $pdo->prepare("SELECT * FROM billiard_tables WHERE id = ?");
$tableStmt->execute([$table_id]);
$table = $tableStmt->fetch();
if (!$table) {
    $table = ['name' => 'POS', 'id' => 0];
}

$billing = ['minutes' => 0, 'amount' => 0, 'session_id' => null, 'start_time' => null, 'end_time' => null, 'customer_name' => null, 'customer_phone' => null, 'package_id' => null];
$now_label = date('d-m-Y H:i');
$is_post = $_SERVER['REQUEST_METHOD'] === 'POST';
$finalizing = $is_post && empty($_POST['set_member']);
$cartKey = $table_id;
$extra_charge_amount = max(0, (int)($_POST['extra_charge_amount'] ?? 0));
$extra_charge_note = trim($_POST['extra_charge_note'] ?? '');
$selected_member_id = $_SESSION['selected_member'][$cartKey] ?? null;
$selected_member_id_post = (int)($_POST['member_id'] ?? 0);
if ($is_post && isset($_POST['set_member'])) {
    $_SESSION['selected_member'][$cartKey] = $selected_member_id_post ?: null;
    header("Location: checkout.php?table_id={$table_id}");
    exit;
}
$selected_member_id = $_SESSION['selected_member'][$cartKey] ?? null;
$member = null;
if ($selected_member_id) {
    $mstmt = $pdo->prepare("SELECT * FROM members WHERE id = ? AND is_active = 1");
    $mstmt->execute([$selected_member_id]);
    $member = $mstmt->fetch();
}

$stmt = $pdo->prepare("SELECT s.*, t.controller_ip, t.relay_channel, tr.rate_per_hour, tr.min_minutes 
    FROM sessions s 
    JOIN billiard_tables t ON s.table_id = t.id 
    LEFT JOIN tariffs tr ON s.tariff_id = tr.id 
    WHERE s.table_id = ? AND s.status IN ('running','paused') 
    ORDER BY s.id DESC LIMIT 1");
$stmt->execute([$table_id]);
$session = $stmt->fetch();
if ($session) {
    $rate_hour = (int)($session['rate_per_hour'] ?? 0);
    $min_minutes_sess = (int)($session['min_minutes'] ?? 0);
    $carry_minutes = (int)($session['total_minutes'] ?? 0);
    $carry_amount  = (int)($session['total_amount'] ?? 0);
    $package = get_package($pdo, $session['package_id']);
    $calc = calculate_billing_with_package($session['start_time'], $rate_hour, $min_minutes_sess, $package, $session);
    $minutes = $carry_minutes + $calc['minutes'];
    $amount = $carry_amount + $calc['amount'];

    if ($finalizing) {
        $upd = $pdo->prepare("UPDATE sessions SET end_time = NOW(), total_minutes = ?, total_amount = ?, status = 'finished' WHERE id = ?");
        $upd->execute([$minutes, $amount, $session['id']]);
        $pdo->prepare("UPDATE billiard_tables SET status = 'idle' WHERE id = ?")->execute([$table_id]);
        if ($session['controller_ip'] && $session['relay_channel']) {
            call_lamp($session['controller_ip'], $session['relay_channel'], 'off');
        }
        $session['end_time'] = date('Y-m-d H:i:s');
    }
    $billing = [
        'minutes' => $minutes,
        'amount' => $amount,
        'session_id' => $session['id'],
        'start_time' => $session['start_time'],
        'end_time' => $session['end_time'] ?? null,
        'customer_name' => $session['customer_name'],
        'customer_phone' => $session['customer_phone'],
        'package_id' => $session['package_id'],
        'carry_minutes' => $carry_minutes,
        'carry_amount' => $carry_amount
    ];
} else {
    $stmt = $pdo->prepare("SELECT s.*, tr.rate_per_hour, tr.min_minutes FROM sessions s LEFT JOIN tariffs tr ON s.tariff_id = tr.id WHERE s.table_id = ? AND s.status = 'finished' ORDER BY s.end_time DESC LIMIT 1");
    $stmt->execute([$table_id]);
    if ($last = $stmt->fetch()) {
        $billing = [
            'minutes' => $last['total_minutes'],
            'amount' => $last['total_amount'],
            'session_id' => $last['id'],
            'start_time' => $last['start_time'],
            'end_time' => $last['end_time'],
            'customer_name' => $last['customer_name'],
            'customer_phone' => $last['customer_phone'],
            'package_id' => $last['package_id'],
            'carry_minutes' => (int)($last['total_minutes'] ?? 0),
            'carry_amount' => (int)($last['total_amount'] ?? 0)
        ];
    }
}

$subtotal = 0;
$total_items = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['qty'];
    $total_items += $item['qty'];
}

$pending_orders = [];
$pending_items = [];
$pending_total = 0;
$table_id_db = $table_id ?: null;
$pendingPosStmt = $pdo->prepare("SELECT * FROM orders WHERE is_paid = 0 AND ((session_id IS NOT NULL AND session_id = ?) OR (session_id IS NULL AND table_id <=> ?))");
$pendingPosStmt->execute([$billing['session_id'], $table_id_db]);
$pending_orders = $pendingPosStmt->fetchAll();
if ($pending_orders) {
    $ids = array_column($pending_orders, 'id');
    $in = implode(',', array_fill(0, count($ids), '?'));
    $itemStmt = $pdo->prepare("SELECT oi.*, p.name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id IN ($in)");
    $itemStmt->execute($ids);
    $pending_items = $itemStmt->fetchAll();
    foreach ($pending_orders as $po) {
        $pending_total += (int)$po['subtotal'];
    }
}

$grand_total = $subtotal + $billing['amount'] + $pending_total + $extra_charge_amount;
$discount_amount = 0;
if ($member) {
    $discount_amount = (int)floor($grand_total * ($member['discount_percent'] / 100));
}
$payable_total = $grand_total - $discount_amount;
$points_earned = $member ? calculate_points($payable_total) : 0;
$order_saved = null;
$error = '';
$now_label = date('d-m-Y H:i');

if ($finalizing) {
    // cek stok untuk cart
    foreach ($cart as $pid => $item) {
        $stock = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
        $stock->execute([$pid]);
        $rowStock = $stock->fetch();
        $available = (int)($rowStock['stock'] ?? 0);
        if ($available > 0 && $item['qty'] > $available) {
            $error = 'Stok tidak cukup untuk ' . htmlspecialchars($item['name']);
            break;
        }
    }
}

if ($finalizing && !$error) {
    $payment_amount = (int)($_POST['payment_amount'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $note = trim($_POST['note'] ?? '');

    if ($payment_amount < $payable_total) {
        $error = 'Nominal bayar kurang.';
    } else {
        $change = $payment_amount - $payable_total;
        // Create single combined order
        $table_id_db = $table_id ?: null;
        $combined_order = $pdo->prepare("INSERT INTO orders (table_id, session_id, member_id, customer_name, customer_phone, operator_id, order_time, total_items, subtotal, grand_total, discount_amount, extra_charge_amount, extra_charge_note, payment_amount, payment_method, change_amount, is_paid, points_earned, note) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $combined_order->execute([
            $table_id_db,
            $billing['session_id'],
            $member['id'] ?? null,
            $billing['customer_name'] ?? null,
            $billing['customer_phone'] ?? null,
            $_SESSION['user']['id'],
            date('Y-m-d H:i:s'),
            $total_items + count($pending_items),
            $pending_total + $subtotal,
            $payable_total,
            $discount_amount,
            $extra_charge_amount,
            $extra_charge_note,
            $payment_amount,
            $payment_method,
            $change,
            1,
            $points_earned,
            $note
        ]);
        $order_id = $pdo->lastInsertId();

        $account_id = get_account_id_for_payment($pdo, $payment_method);
        if ($account_id) {
            add_journal($pdo, $account_id, 'in', $payment_amount, 'Pembayaran Checkout #' . $order_id, 'order', $order_id);
        }

        // Insert cart items
        if ($cart) {
            $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, qty, price, subtotal) VALUES (?,?,?,?,?)");
            foreach ($cart as $pid => $item) {
                $itemStmt->execute([$order_id, $pid, $item['qty'], $item['price'], $item['price'] * $item['qty']]);
                adjust_stock($pdo, $pid, -1 * $item['qty']);
            }
        }
        // Insert pending items into combined order
        if ($pending_items) {
            $itemStmt2 = $pdo->prepare("INSERT INTO order_items (order_id, product_id, qty, price, subtotal) VALUES (?,?,?,?,?)");
            foreach ($pending_items as $pi) {
                $itemStmt2->execute([$order_id, $pi['product_id'], $pi['qty'], $pi['price'], $pi['subtotal']]);
            }
        }
        // Mark pending POS orders as merged (not counted)
        if ($pending_orders) {
            $ids = array_column($pending_orders, 'id');
            $in = implode(',', array_fill(0, count($ids), '?'));
            $params = $ids;
            $mergeUpd = $pdo->prepare("UPDATE orders SET is_paid = 2, note = CONCAT(IFNULL(note,''),' [merged]') WHERE id IN ($in)");
            $mergeUpd->execute($params);
        }

        unset($_SESSION['cart'][$cartKey]);
        $order_saved = [
            'payment_amount' => $payment_amount,
            'change' => $change,
            'grand_total' => $grand_total,
            'order_id' => $order_id
        ];
    }
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<h4 class="mb-3">Checkout <?php echo $table_id ? 'Meja ' . htmlspecialchars($table['name']) : 'POS'; ?></h4>
<?php if ($error): ?><div class="alert alert-danger py-2"><?php echo $error; ?></div><?php endif; ?>

<div class="row">
    <div class="col-md-6">
        <div class="card bg-secondary text-light mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Billing Billiard</span>
                <form method="post" class="d-flex align-items-center gap-1">
                    <select name="member_id" class="form-select form-select-sm bg-dark text-light" style="width:auto;">
                        <option value="">Non Member</option>
                        <?php
                        $mAll = $pdo->query("SELECT * FROM members WHERE is_active = 1 ORDER BY name")->fetchAll();
                        foreach ($mAll as $m): ?>
                            <option value="<?php echo $m['id']; ?>" <?php echo ($selected_member_id == $m['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($m['name'] . ' (' . $m['phone'] . ') - ' . $m['discount_percent'] . '%'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline-light btn-sm" name="set_member">Set</button>
                </form>
            </div>
            <div class="card-body">
                    <?php if ($billing['session_id']): ?>
                        <div>Customer: <strong><?php echo htmlspecialchars($billing['customer_name'] ?? '-'); ?></strong></div>
                        <?php if ($member): ?><div>Member: <?php echo htmlspecialchars($member['name']); ?> (<?php echo htmlspecialchars($member['phone']); ?>)</div><?php endif; ?>
                        <?php if ($billing['package_id']): ?>
                            <?php $pkgView = get_package($pdo, $billing['package_id']); ?>
                            <?php if ($pkgView): ?><div>Paket: <?php echo htmlspecialchars($pkgView['name']); ?> (<?php echo $pkgView['duration_minutes']; ?> mnt - <?php echo format_rupiah($pkgView['special_price']); ?>)</div><?php endif; ?>
                        <?php endif; ?>
                        <div>Start: <?php echo format_datetime($billing['start_time']); ?></div>
                        <div>End: <?php echo $billing['end_time'] ? format_datetime($billing['end_time']) : '<span class="text-warning">Berjalan</span>'; ?></div>
                        <?php if (!empty($billing['carry_minutes'])): ?>
                            <div class="text-info small">Akumulasi meja sebelumnya: <?php echo human_duration($billing['carry_minutes']); ?> (<?php echo format_rupiah($billing['carry_amount']); ?>)</div>
                        <?php endif; ?>
                        <div>Durasi: <?php echo human_duration($billing['minutes']); ?></div>
                        <div class="fs-4 mt-2"><?php echo format_rupiah($billing['amount']); ?></div>
                    <?php else: ?>
                        <div class="text-muted">Tidak ada sesi aktif/terakhir.</div>
                    <?php endif; ?>
            </div>
        </div>
        <div class="card bg-secondary text-light">
            <div class="card-header">Cart POS</div>
            <div class="card-body">
                <?php if ($pending_orders): ?>
                    <div class="mb-2">POS yang sudah dicatat (bayar nanti):</div>
                    <ul class="list-group list-group-flush mb-3">
                        <?php foreach ($pending_orders as $po): ?>
                            <li class="list-group-item bg-secondary text-light d-flex justify-content-between">
                                <span>Order #<?php echo $po['id']; ?> (<?php echo format_datetime($po['order_time']); ?>)</span>
                                <span><?php echo format_rupiah($po['subtotal']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($pending_items): ?>
                        <div class="mb-2 small text-muted">Detail item pending:</div>
                        <ul class="list-group list-group-flush mb-3">
                            <?php foreach ($pending_items as $pi): ?>
                                <li class="list-group-item bg-secondary text-light d-flex justify-content-between">
                                    <span><?php echo htmlspecialchars($pi['name'] ?? ('Item #' . $pi['product_id'])); ?> x <?php echo $pi['qty']; ?></span>
                                    <span><?php echo format_rupiah($pi['subtotal']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php endif; ?>
                <ul class="list-group list-group-flush">
                    <?php if ($cart): ?>
                        <?php foreach ($cart as $item): ?>
                            <li class="list-group-item bg-secondary text-light d-flex justify-content-between">
                                <span><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['qty']; ?></span>
                                <span><?php echo format_rupiah($item['price'] * $item['qty']); ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item bg-secondary text-light">Cart kosong</li>
                    <?php endif; ?>
                </ul>
                <div class="d-flex justify-content-between mt-2">
                    <span>Subtotal POS</span>
                    <span><?php echo format_rupiah($subtotal); ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-secondary text-light">
            <div class="card-header">Pembayaran</div>
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <span>Total Billiard</span><span><?php echo format_rupiah($billing['amount']); ?></span>
                </div>
                    <div class="d-flex justify-content-between">
                        <span>POS Pending (sudah dicatat)</span><span><?php echo format_rupiah($pending_total); ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Subtotal POS (cart)</span><span><?php echo format_rupiah($subtotal); ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Extra Charge</span><span><?php echo format_rupiah($extra_charge_amount); ?></span>
                    </div>
                    <div class="d-flex justify-content-between fw-bold fs-5 mt-2">
                    <span>Grand Total</span><span><?php echo format_rupiah($grand_total); ?></span>
                    </div>
                <?php if ($member): ?>
                    <div class="d-flex justify-content-between text-info">
                        <span>Diskon Member (<?php echo $member['discount_percent']; ?>%)</span><span>-<?php echo format_rupiah($discount_amount); ?></span>
                    </div>
                <?php endif; ?>
                <div class="d-flex justify-content-between fw-bold fs-5">
                    <span>Total Bayar</span><span><?php echo format_rupiah($payable_total); ?></span>
                </div>
                <?php if (!$order_saved): ?>
                    <form method="post" class="mt-3">
                        <div class="mb-3">
                            <label class="form-label">Nominal Bayar</label>
                            <input type="number" name="payment_amount" class="form-control" value="<?php echo $grand_total; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Metode</label>
                            <select name="payment_method" class="form-select">
                                <option value="cash">Cash</option>
                                <option value="transfer">Transfer</option>
                                <option value="qris">QRIS</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Extra Charge</label>
                            <input type="number" name="extra_charge_amount" class="form-control" value="<?php echo $extra_charge_amount; ?>" min="0">
                            <small class="text-muted">Misal service charge / bawa makanan luar</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan Extra Charge</label>
                            <input type="text" name="extra_charge_note" class="form-control" value="<?php echo htmlspecialchars($extra_charge_note); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <input type="text" name="note" class="form-control">
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="/billiard_pos/pos/pos.php?table_id=<?php echo $table_id; ?>" class="btn btn-outline-light">Kembali</a>
                            <button type="submit" class="btn btn-success" <?php echo $grand_total <=0 ? 'disabled' : ''; ?>>Proses Pembayaran</button>
                        </div>
                    </form>
                <?php else: ?>
                    <?php
                        $receipt_items = [];
                        foreach ($pending_items as $pi) {
                            $receipt_items[] = [
                                'name' => $pi['name'] ?? ('Item #' . $pi['product_id']),
                                'qty' => $pi['qty'],
                                'subtotal' => $pi['subtotal']
                            ];
                        }
                        foreach ($cart as $item) {
                            $receipt_items[] = [
                                'name' => $item['name'],
                                'qty' => $item['qty'],
                                'subtotal' => $item['price'] * $item['qty']
                            ];
                        }
                    ?>
                    <div class="alert alert-success mt-3">
                        Pembayaran tersimpan. Kembalian: <?php echo format_rupiah($order_saved['change']); ?>
                    </div>
                    <div class="card bg-dark text-light mt-3" id="receipt">
                        <div class="card-body">
                            <div class="text-center mb-2">
                                <?php if (!empty($company['logo']) && file_exists(__DIR__ . '/../' . ltrim($company['logo'], '/'))): ?>
                                    <img src="/billiard_pos/<?php echo ltrim($company['logo'], '/'); ?>" alt="Logo" style="height:40px;">
                                <?php endif; ?>
                                <div class="fw-bold"><?php echo htmlspecialchars($company['name'] ?? 'Billiard POS'); ?></div>
                                <?php if (!empty($company['tagline'])): ?><div class="small"><?php echo htmlspecialchars($company['tagline']); ?></div><?php endif; ?>
                                <?php if (!empty($company['address'])): ?><div class="small"><?php echo htmlspecialchars($company['address']); ?></div><?php endif; ?>
                                <?php if (!empty($company['phone'])): ?><div class="small">Telp: <?php echo htmlspecialchars($company['phone']); ?></div><?php endif; ?>
                            </div>
                            <div class="text-center">Invoice: #<?php echo $order_saved['order_id']; ?></div>
                            <div class="text-center small"><?php echo $now_label; ?></div>
                            <div class="receipt-sep"></div>
                            <div>Customer: <?php echo htmlspecialchars($billing['customer_name'] ?? '-'); ?></div>
                            <div>HP: <?php echo htmlspecialchars($billing['customer_phone'] ?? '-'); ?></div>
                            <div>Meja: <?php echo htmlspecialchars($table['name'] ?? $table_id); ?></div>
                            <div>Kasir: <?php echo htmlspecialchars($_SESSION['user']['username'] ?? '-'); ?></div>
                            <?php if ($member): ?><div>Member: <?php echo htmlspecialchars($member['name']); ?> | Diskon <?php echo $member['discount_percent']; ?>%</div><?php endif; ?>
                            <?php if (!empty($billing['package_id'])): ?>
                                <?php $pkgView = get_package($pdo, $billing['package_id']); ?>
                                <?php if ($pkgView): ?><div>Paket: <?php echo htmlspecialchars($pkgView['name']); ?> (<?php echo $pkgView['duration_minutes']; ?> mnt)</div><?php endif; ?>
                            <?php endif; ?>
                            <div class="receipt-sep"></div>
                            <div>Mulai : <?php echo format_datetime($billing['start_time']); ?></div>
                            <div>Selesai: <?php echo $billing['end_time'] ? format_datetime($billing['end_time']) : '-'; ?></div>
                            <?php if (!empty($billing['carry_minutes'])): ?>
                                <div class="receipt-row"><span>Akumulasi</span><span><?php echo human_duration($billing['carry_minutes']); ?> / <?php echo format_rupiah($billing['carry_amount']); ?></span></div>
                            <?php endif; ?>
                            <div class="receipt-row"><span>Durasi</span><span><?php echo human_duration($billing['minutes']); ?></span></div>
                            <div class="receipt-row"><span>Billing</span><span><?php echo format_rupiah($billing['amount']); ?></span></div>
                            <div class="receipt-sep"></div>
                            <?php if ($receipt_items): ?>
                                <div class="fw-bold">Detail POS</div>
                                <?php foreach ($receipt_items as $ri): ?>
                                    <div class="receipt-row"><span><?php echo htmlspecialchars($ri['name']); ?> x <?php echo $ri['qty']; ?></span><span><?php echo format_rupiah($ri['subtotal']); ?></span></div>
                                <?php endforeach; ?>
                                <div class="receipt-sep"></div>
                            <?php endif; ?>
                            <div class="receipt-row"><span>Total POS</span><span><?php echo format_rupiah($pending_total + $subtotal); ?></span></div>
                            <?php if ($member): ?>
                                <div class="receipt-row"><span>Diskon Member (<?php echo $member['discount_percent']; ?>%)</span><span>-<?php echo format_rupiah($discount_amount); ?></span></div>
                            <?php endif; ?>
                            <?php if ($extra_charge_amount > 0): ?>
                                <div class="receipt-row"><span>Extra Charge</span><span><?php echo format_rupiah($extra_charge_amount); ?></span></div>
                                <?php if ($extra_charge_note): ?><div class="small text-muted"><?php echo htmlspecialchars($extra_charge_note); ?></div><?php endif; ?>
                            <?php endif; ?>
                            <div class="receipt-row fw-bold"><span>Grand Total</span><span><?php echo format_rupiah($grand_total); ?></span></div>
                            <div class="receipt-row fw-bold"><span>Total Bayar</span><span><?php echo format_rupiah($payable_total); ?></span></div>
                            <div class="receipt-row"><span>Bayar</span><span><?php echo format_rupiah($order_saved['payment_amount']); ?></span></div>
                            <div class="receipt-row"><span>Kembali</span><span><?php echo format_rupiah($order_saved['change']); ?></span></div>
                            <?php if ($member && $points_earned > 0): ?>
                                <div class="receipt-row"><span>Poin Baru</span><span><?php echo $points_earned; ?></span></div>
                            <?php endif; ?>
                            <div class="text-center mt-2 small">Terima kasih</div>
                        </div>
                    </div>
                    <div class="no-print mt-2 d-flex flex-wrap gap-2">
                        <a class="btn btn-success" target="_blank" href="/billiard_pos/pos/print_receipt.php?order_id=<?php echo $order_saved['order_id']; ?>">Cetak Struk (80mm)</a>
                        <a class="btn btn-outline-light" href="/billiard_pos/index.php">Kembali ke Dashboard</a>
                        <a class="btn btn-primary" href="/billiard_pos/index.php">Selesai</a>
                        <a class="btn btn-danger" href="/billiard_pos/pos/void_order.php?order_id=<?php echo $order_saved['order_id']; ?>&table_id=<?php echo $table_id; ?>" onclick="return confirm('Batalkan/retur order ini? Stok akan dikembalikan.');">Batalkan / Retur</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
