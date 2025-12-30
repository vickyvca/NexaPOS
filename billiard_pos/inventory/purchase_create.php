<?php
require_once __DIR__ . '/../includes/functions.php';
check_login('admin');

$products = $pdo->query("SELECT * FROM products WHERE is_active = 1 ORDER BY name")->fetchAll();
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier = trim($_POST['supplier'] ?? '');
    $note = trim($_POST['note'] ?? '');
    $items = $_POST['items'] ?? [];

    if (!$items) {
        $error = 'Tidak ada item.';
    } else {
        $total = 0;
        foreach ($items as $it) {
            $qty = (int)($it['qty'] ?? 0);
            $cost = (int)($it['cost'] ?? 0);
            if ($qty > 0 && $cost >= 0) {
                $total += $qty * $cost;
            }
        }
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO purchases (supplier, note, total, operator_id, purchase_time) VALUES (?,?,?,?,?)");
            $stmt->execute([$supplier, $note, $total, $_SESSION['user']['id'], date('Y-m-d H:i:s')]);
            $purchase_id = $pdo->lastInsertId();
            $itemStmt = $pdo->prepare("INSERT INTO purchase_items (purchase_id, product_id, qty, cost_price, subtotal) VALUES (?,?,?,?,?)");
            foreach ($items as $it) {
                $pid = (int)($it['product_id'] ?? 0);
                $qty = (int)($it['qty'] ?? 0);
                $cost = (int)($it['cost'] ?? 0);
                if ($pid && $qty > 0) {
                    $itemStmt->execute([$purchase_id, $pid, $qty, $cost, $qty * $cost]);
                    adjust_stock($pdo, $pid, $qty);
                }
            }
            $pdo->commit();
            $success = 'Pembelian disimpan.';
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Gagal simpan: ' . $e->getMessage();
        }
    }
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Pembelian Barang</h4>
    <a href="/billiard_pos/inventory/purchase_list.php" class="btn btn-outline-light btn-sm">Riwayat</a>
</div>
<?php if ($success): ?><div class="alert alert-success py-2"><?php echo $success; ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-danger py-2"><?php echo $error; ?></div><?php endif; ?>
<div class="card bg-secondary text-light">
    <div class="card-body">
        <form method="post" id="purchaseForm">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Supplier</label>
                    <input type="text" name="supplier" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Catatan</label>
                    <input type="text" name="note" class="form-control">
                </div>
            </div>
            <hr>
            <div id="itemsWrap">
                <div class="row g-2 align-items-end mb-2 item-row">
                    <div class="col-md-5">
                        <label class="form-label">Produk</label>
                        <select name="items[0][product_id]" class="form-select">
                            <?php foreach ($products as $p): ?>
                                <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?> (Stok: <?php echo $p['stock']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Qty</label>
                        <input type="number" name="items[0][qty]" class="form-control" value="1" min="1">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Harga Beli</label>
                        <input type="number" name="items[0][cost]" class="form-control" value="0" min="0">
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-outline-light w-100" onclick="addItemRow()">+</button>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <button class="btn btn-success">Simpan Pembelian</button>
            </div>
        </form>
    </div>
</div>
<script>
let itemIndex = 1;
function addItemRow() {
    const wrap = document.getElementById('itemsWrap');
    const row = document.createElement('div');
    row.className = 'row g-2 align-items-end mb-2 item-row';
    row.innerHTML = `
        <div class="col-md-5">
            <select name="items[${itemIndex}][product_id]" class="form-select">
                <?php foreach ($products as $p): ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?> (Stok: <?php echo $p['stock']; ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3"><input type="number" name="items[${itemIndex}][qty]" class="form-control" value="1" min="1"></div>
        <div class="col-md-3"><input type="number" name="items[${itemIndex}][cost]" class="form-control" value="0" min="0"></div>
        <div class="col-md-1"><button type="button" class="btn btn-outline-danger w-100" onclick="this.closest('.item-row').remove()">-</button></div>
    `;
    wrap.appendChild(row);
    itemIndex++;
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
