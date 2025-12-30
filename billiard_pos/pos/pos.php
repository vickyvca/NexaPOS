<?php
require_once __DIR__ . '/../includes/functions.php';
check_login();

$table_id = (int)($_GET['table_id'] ?? 0);
$products = $pdo->query("SELECT * FROM products WHERE is_active = 1 ORDER BY category, name")->fetchAll();
$categories = [];
if ($products) {
    $categories = array_values(array_unique(array_map(function($p){ return $p['category'] ?? 'lain'; }, $products)));
    sort($categories);
}
$members = $pdo->query("SELECT * FROM members WHERE is_active = 1 ORDER BY name")->fetchAll();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
$cartKey = $table_id ?: 0;
if (!isset($_SESSION['cart'][$cartKey])) {
    $_SESSION['cart'][$cartKey] = [];
}

$message = $_SESSION['flash_success'] ?? '';
$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_member'])) {
    $member_id = (int)($_POST['member_id'] ?? 0);
    $_SESSION['selected_member'][$cartKey] = $member_id ?: null;
    header("Location: pos.php?table_id={$table_id}");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $qty = (int)($_POST['qty'] ?? 1);
    if ($product_id && $qty > 0) {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        if ($row = $stmt->fetch()) {
            if (!isset($_SESSION['cart'][$cartKey][$product_id])) {
                $_SESSION['cart'][$cartKey][$product_id] = ['name' => $row['name'], 'price' => $row['price'], 'qty' => 0];
            }
            $_SESSION['cart'][$cartKey][$product_id]['qty'] += $qty;
        }
    }
    header("Location: pos.php?table_id={$table_id}");
    exit;
}

// Update qty cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_qty'])) {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $qty = max(1, (int)($_POST['qty'] ?? 1));
    if (isset($_SESSION['cart'][$cartKey][$product_id])) {
        $_SESSION['cart'][$cartKey][$product_id]['qty'] = $qty;
    }
    header("Location: pos.php?table_id={$table_id}");
    exit;
}

if (isset($_GET['remove'])) {
    $pid = (int)$_GET['remove'];
    unset($_SESSION['cart'][$cartKey][$pid]);
    header("Location: pos.php?table_id={$table_id}");
    exit;
}

$cart_items = $_SESSION['cart'][$cartKey];
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['qty'];
}
$is_table = $table_id > 0;

// Riwayat order terakhir (untuk void/print)
if ($is_table) {
    $recentStmt = $pdo->prepare("
        SELECT id, order_time, grand_total, is_paid, table_id, customer_name
        FROM orders 
        WHERE (table_id = ? OR session_id IN (SELECT id FROM sessions WHERE table_id = ?))
        ORDER BY id DESC LIMIT 5
    ");
    $recentStmt->execute([$table_id, $table_id]);
} else {
    $recentStmt = $pdo->prepare("SELECT id, order_time, grand_total, is_paid, table_id, customer_name FROM orders WHERE table_id IS NULL ORDER BY id DESC LIMIT 5");
    $recentStmt->execute();
}
$recentOrders = $recentStmt->fetchAll();

$running = $table_id ? get_running_session($pdo, $table_id) : null;
$prefill_customer = $running['customer_name'] ?? ($_SESSION['last_customer'][$cartKey] ?? '');
$prefill_phone = $running['customer_phone'] ?? ($_SESSION['last_customer_phone'][$cartKey] ?? '');
$selected_member_id = $_SESSION['selected_member'][$cartKey] ?? null;
$selected_member = null;
if ($selected_member_id) {
    foreach ($members as $m) {
        if ($m['id'] == $selected_member_id) {
            $selected_member = $m;
            break;
        }
    }
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <div class="section-title mb-1"><span class="dot"></span><span>POS Penjualan</span></div>
        <small class="text-muted"><?php echo $table_id ? 'Meja ' . $table_id : 'Tanpa meja (POS mandiri)'; ?></small>
    </div>
    <div class="d-flex gap-2">
        <a href="/billiard_pos/index.php" class="btn btn-ghost btn-sm">Dashboard</a>
        <?php if ($table_id): ?>
            <a href="/billiard_pos/pos/checkout.php?table_id=<?php echo $table_id; ?>" class="btn btn-warning btn-sm fw-bold">Checkout Gabungan</a>
        <?php else: ?>
            <a href="/billiard_pos/pos/checkout.php?table_id=0" class="btn btn-warning btn-sm fw-bold">Checkout & Cetak POS</a>
        <?php endif; ?>
    </div>
</div>
<?php if ($message): ?><div class="alert alert-success py-2"><?php echo $message; ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger py-2"><?php echo $error; ?></div><?php endif; ?>
<div class="row g-3">
    <div class="col-lg-5 col-md-6">
        <div class="card bg-secondary text-light mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold">Member & Customer</span>
                <?php if ($selected_member): ?><span class="badge-soft accent">Diskon <?php echo $selected_member['discount_percent']; ?>%</span><?php endif; ?>
            </div>
            <div class="card-body">
                <form method="post" class="mb-3">
                    <label class="form-label">Pilih Member (diskon otomatis)</label>
                    <div class="input-group">
                        <select name="member_id" class="form-select">
                            <option value="">-- Non Member --</option>
                            <?php foreach ($members as $m): ?>
                                <option value="<?php echo $m['id']; ?>" <?php echo ($selected_member_id == $m['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($m['name'] . ' (' . $m['phone'] . ') - ' . $m['discount_percent'] . '%'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-outline-light" name="set_member">Set</button>
                    </div>
                </form>
                <?php if ($selected_member): ?>
                    <div class="alert alert-info py-2 mt-2">
                        Member: <?php echo htmlspecialchars($selected_member['name']); ?> | Diskon <?php echo $selected_member['discount_percent']; ?>% | Poin: <?php echo $selected_member['points']; ?>
                    </div>
                <?php endif; ?>
                <?php if ($running): ?>
                <div class="alert alert-dark py-2 mt-2">
                    Meja: <?php echo htmlspecialchars($running['table_name'] ?? ('#'.$table_id)); ?><br>
                    Customer: <?php echo htmlspecialchars($running['customer_name'] ?? '-'); ?> (<?php echo htmlspecialchars($running['customer_phone'] ?? '-'); ?>)
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card bg-secondary text-light">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold">Pilih Produk</span>
                <span class="badge bg-dark"><?php echo count($products); ?> item</span>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
                    <div class="pill-filter mb-2">
                        <button class="btn btn-outline-light btn-sm filter-btn active" data-cat="all">Semua</button>
                        <?php foreach ($categories as $cat): ?>
                            <button class="btn btn-outline-light btn-sm filter-btn" data-cat="<?php echo htmlspecialchars($cat); ?>"><?php echo ucfirst($cat); ?></button>
                        <?php endforeach; ?>
                    </div>
                    <div class="mb-2">
                        <input type="text" id="productSearch" class="form-control form-control-sm bg-dark text-light" placeholder="Cari produk...">
                    </div>
                </div>
                <div class="row g-2" id="productGrid">
                    <?php foreach ($products as $p): ?>
                        <div class="col-6" data-cat="<?php echo htmlspecialchars($p['category']); ?>" data-name="<?php echo htmlspecialchars(strtolower($p['name'])); ?>">
                            <form method="post">
                                <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                <input type="hidden" name="qty" value="1">
                                <div class="card h-100 product-card">
                                    <div class="card-body d-flex flex-column justify-content-between">
                                        <div>
                                        <div class="product-name"><?php echo htmlspecialchars($p['name']); ?></div>
                                        <div class="small text-muted"><?php echo htmlspecialchars($p['category']); ?></div>
                                    </div>
                                        <div class="price mt-2"><?php echo format_rupiah($p['price']); ?></div>
                                        <button class="btn btn-primary btn-sm mt-2" name="add_item">Tambah</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php if ($running): ?>
            <div class="card bg-secondary text-light mt-3">
                <div class="card-header">Sesi Billiard</div>
                <div class="card-body">
                    <div>Customer: <?php echo htmlspecialchars($running['customer_name'] ?? '-'); ?> (<?php echo htmlspecialchars($running['customer_phone'] ?? '-'); ?>)</div>
                    <div>Mulai: <?php echo format_datetime($running['start_time']); ?></div>
                    <div>Durasi berjalan: <span data-start-time="<?php echo $running['start_time']; ?>">00:00:00</span></div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="col-lg-7 col-md-6">
            <div class="card bg-secondary text-light">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Cart & Ringkasan</span>
                    <span class="fw-bold" id="posTotalDisplay"><?php echo format_rupiah($subtotal); ?></span>
                </div>
                <div class="card-body">
                <table class="table table-dark table-striped cart-table">
                    <thead><tr><th>Item</th><th>Harga</th><th>Qty</th><th>Subtotal</th><th></th></tr></thead>
                    <tbody>
                        <?php if ($cart_items): ?>
                            <?php foreach ($cart_items as $pid => $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo format_rupiah($item['price']); ?></td>
                                    <td>
                                        <form method="post" class="d-flex align-items-center gap-1">
                                            <input type="hidden" name="product_id" value="<?php echo $pid; ?>">
                                            <input type="number" name="qty" min="1" class="form-control form-control-sm bg-dark text-light" style="width:70px;" value="<?php echo $item['qty']; ?>">
                                            <button class="btn btn-sm btn-outline-light" name="update_qty">Set</button>
                                        </form>
                                    </td>
                                    <td><?php echo format_rupiah($item['price'] * $item['qty']); ?></td>
                                    <td><a href="?table_id=<?php echo $table_id; ?>&remove=<?php echo $pid; ?>" class="btn btn-sm btn-danger">X</a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-center text-muted">Cart kosong</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php $formAction = $is_table ? 'save_order.php' : 'checkout.php?table_id=0'; ?>
                <form method="post" action="<?php echo $formAction; ?>" id="posForm">
                    <input type="hidden" name="table_id" value="<?php echo $table_id; ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Customer</label>
                            <input type="text" name="customer_name" class="form-control" value="<?php echo htmlspecialchars($prefill_customer); ?>" placeholder="Opsional">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">No. HP</label>
                            <input type="text" name="customer_phone" class="form-control" value="<?php echo htmlspecialchars($prefill_phone); ?>" placeholder="Opsional">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Bayar</label>
                            <input type="number" name="payment_amount" id="payInput" class="form-control" value="<?php echo $subtotal; ?>" required>
                            <small id="changeInfo" class="text-info"></small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Metode</label>
                            <select name="payment_method" class="form-select">
                                <option value="cash">Cash</option>
                                <option value="transfer">Transfer</option>
                                <option value="qris">QRIS</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Extra Charge</label>
                            <input type="number" name="extra_charge_amount" class="form-control" value="0" min="0">
                            <small class="text-muted">Service charge / bawa makanan luar</small>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Keterangan Extra</label>
                            <input type="text" name="extra_charge_note" class="form-control" placeholder="Catatan charge (opsional)">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Catatan</label>
                            <input type="text" name="note" class="form-control">
                        </div>
                    </div>
                    <div class="mt-3 d-flex justify-content-between align-items-center gap-2 flex-wrap">
                        <?php if ($is_table): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="pay_later" name="pay_later" checked>
                                <label class="form-check-label" for="pay_later">Tambah ke billing meja</label>
                            </div>
                        <?php endif; ?>
                        <div class="ms-auto d-flex gap-2">
                            <a href="/billiard_pos/index.php" class="btn btn-outline-light">Back</a>
                            <?php if ($is_table): ?>
                                <button type="submit" class="btn btn-primary" <?php echo $subtotal <=0 ? 'disabled' : ''; ?>>Simpan ke Meja</button>
                                <a class="btn btn-warning" href="/billiard_pos/pos/checkout.php?table_id=<?php echo $table_id; ?>">Checkout</a>
                            <?php else: ?>
                                <button type="submit" class="btn btn-success" <?php echo $subtotal <=0 ? 'disabled' : ''; ?>>Checkout POS</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
            <div class="card bg-secondary text-light mt-3">
                <div class="card-header">Order Terakhir</div>
                <div class="card-body">
                    <?php if ($recentOrders): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($recentOrders as $ro): ?>
                                <?php
                                    $itemsStmt = $pdo->prepare("SELECT oi.qty, oi.price, p.name FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE oi.order_id = ?");
                                    $itemsStmt->execute([$ro['id']]);
                                    $items = $itemsStmt->fetchAll();
                                    $detail = [];
                                    foreach ($items as $it) {
                                        $detail[] = htmlspecialchars($it['name']) . ' x' . $it['qty'] . ' (' . format_rupiah($it['price']) . ')';
                                    }
                                    $allowVoid = true;
                                    if ($is_table) {
                                        if ((int)$ro['table_id'] !== $table_id) {
                                            $allowVoid = false;
                                        }
                                        $currentCustomer = trim($running['customer_name'] ?? '');
                                        if ($currentCustomer !== '' && strcasecmp($currentCustomer, trim($ro['customer_name'] ?? '')) !== 0) {
                                            $allowVoid = false;
                                        }
                                    }
                                ?>
                                <li class="list-group-item bg-secondary text-light">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <div class="fw-bold">#<?php echo $ro['id']; ?> | <?php echo format_datetime($ro['order_time']); ?></div>
                                            <div><?php echo format_rupiah($ro['grand_total']); ?> <span class="text-muted small">Customer: <?php echo htmlspecialchars($ro['customer_name'] ?? '-'); ?></span></div>
                                            <div class="small text-muted">Item: <?php echo implode('; ', $detail); ?></div>
                                        </div>
                                        <div class="d-flex flex-column gap-1 align-items-end">
                                            <a class="btn btn-sm btn-outline-light" target="_blank" href="/billiard_pos/pos/print_receipt.php?order_id=<?php echo $ro['id']; ?>">Print</a>
                                            <?php if ((int)$ro['is_paid'] !== 3 && $allowVoid): ?>
                                                <a class="btn btn-sm btn-danger void-btn" href="/billiard_pos/pos/void_order.php?order_id=<?php echo $ro['id']; ?>&table_id=<?php echo $table_id; ?>" data-order="<?php echo $ro['id']; ?>" data-table="<?php echo $table_id; ?>">Void</a>
                                            <?php elseif ((int)$ro['is_paid'] === 3): ?>
                                                <span class="badge bg-danger">Voided</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Lock</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="text-muted">Belum ada order.</div>
                    <?php endif; ?>
                </div>
            </div>
</div>
</div>
</div>
<div class="no-print pos-floating d-flex justify-content-between align-items-center gap-2 bg-dark text-light p-2" style="position:fixed;bottom:0;left:0;right:0;z-index:999;border-top:1px solid #1f2937;">
    <div>
        <div class="small text-muted mb-0">Total POS</div>
        <div class="fw-bold" id="posTotalFloating"><?php echo format_rupiah($subtotal); ?></div>
    </div>
    <div class="d-flex gap-2 ms-auto">
        <?php if ($is_table): ?>
            <button class="btn btn-success" onclick="document.getElementById('posForm').submit();" <?php echo $subtotal <=0 ? 'disabled' : ''; ?>>Simpan ke Meja</button>
            <a class="btn btn-warning" href="/billiard_pos/pos/checkout.php?table_id=<?php echo $table_id; ?>">Checkout</a>
        <?php else: ?>
            <button class="btn btn-success" onclick="document.getElementById('posForm').submit();" <?php echo $subtotal <=0 ? 'disabled' : ''; ?>>Checkout POS</button>
        <?php endif; ?>
    </div>
</div>
<script>
(function(){
    const total = <?php echo (int)$subtotal; ?>;
    const payInput = document.getElementById('payInput');
    const changeInfo = document.getElementById('changeInfo');
    const totalDisplay = document.getElementById('posTotalDisplay');
    const totalFloating = document.getElementById('posTotalFloating');
    if (totalDisplay) totalDisplay.textContent = new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',minimumFractionDigits:0}).format(total);
    if (totalFloating) totalFloating.textContent = new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',minimumFractionDigits:0}).format(total);
    const fmt = (n) => new Intl.NumberFormat('id-ID',{style:'currency',currency:'IDR',minimumFractionDigits:0}).format(n);
    function updateChange(){
        if (!payInput || !changeInfo) return;
        const pay = parseInt(payInput.value || '0', 10);
        const change = pay - total;
        changeInfo.textContent = 'Total: ' + fmt(total) + ' | Kembali: ' + fmt(change);
    }
    if (payInput) payInput.addEventListener('input', updateChange);
    updateChange();
})();

// Void tanpa pindah halaman (fetch)
document.addEventListener('click', function(e){
    const btn = e.target.closest('.void-btn');
    if (!btn) return;
    e.preventDefault();
    if (!confirm('Batalkan/retur order ini? Stok akan dikembalikan.')) return;
    fetch(btn.href, { method:'GET', headers: {'X-Requested-With':'XMLHttpRequest'} })
        .then(()=>window.location.reload());
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
