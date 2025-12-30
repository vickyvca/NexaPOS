<?php
require_once __DIR__ . '/../middleware.php';
ensure_role(['admin','kasir']);
check_csrf();
$pdo = getPDO();
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $saleId = (int)$_POST['sale_id'];
    $itemId = (int)$_POST['item_id'];
    $qty = (float)$_POST['qty'];
    $note = trim($_POST['note'] ?? '');
    $sale = $pdo->prepare("SELECT * FROM sales WHERE id=?");
    $sale->execute([$saleId]);
    if ($sale->fetch()) {
        $pdo->beginTransaction();
        try {
            $returnNo = 'RT' . date('ymdHis');
            $pdo->prepare("INSERT INTO returns(sale_id, return_no, date, total, note, created_by) VALUES(?,?,?,?,?,?)")
                ->execute([$saleId,$returnNo,date('Y-m-d'),0,$note,current_user()['id']]);
            $rid = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT price FROM sale_items WHERE sale_id=? AND item_id=?");
            $stmt->execute([$saleId,$itemId]);
            $price = (float)$stmt->fetchColumn();
            $subtotal = $qty * $price;
            $pdo->prepare("INSERT INTO return_items(return_id,item_id,qty,price,subtotal) VALUES(?,?,?,?,?)")
                ->execute([$rid,$itemId,$qty,$price,$subtotal]);
            $pdo->prepare("UPDATE items SET stock = stock + ? WHERE id=?")->execute([$qty,$itemId]);
            $pdo->prepare("INSERT INTO stock_moves(item_id, ref_type, ref_id, date, qty_in, qty_out, note, created_by) VALUES(?,?,?,?,?,?,?,?)")
                ->execute([$itemId,'return',$rid,date('Y-m-d'),$qty,0,'Retur penjualan',current_user()['id']]);
            $pdo->commit();
            $message = 'Retur berhasil';
        } catch (Exception $e) {
            $pdo->rollBack();
            log_error($e->getMessage());
            $message = 'Gagal retur';
        }
    } else {
        $message = 'Invoice tidak ditemukan';
    }
}
$sales = $pdo->query("SELECT id,sale_no FROM sales ORDER BY id DESC LIMIT 50")->fetchAll();
$items = $pdo->query("SELECT id,name FROM items ORDER BY name")->fetchAll();
?>
<?php include __DIR__ . '/../layout/header.php'; ?>
<h4>Retur Penjualan</h4>
<?php if ($message): ?><div class="alert alert-info"><?= htmlspecialchars($message); ?></div><?php endif; ?>
<form method="post">
    <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">
    <div class="row g-2">
        <div class="col-md-3">
            <label>Invoice</label>
            <select class="form-select" name="sale_id" required>
                <?php foreach ($sales as $s): ?><option value="<?= $s['id']; ?>"><?= htmlspecialchars($s['sale_no']); ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label>Barang</label>
            <select class="form-select" name="item_id" required>
                <?php foreach ($items as $i): ?><option value="<?= $i['id']; ?>"><?= htmlspecialchars($i['name']); ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label>Qty</label>
            <input type="number" class="form-control" name="qty" step="0.01" required>
        </div>
        <div class="col-md-3">
            <label>Alasan</label>
            <input class="form-control" name="note">
        </div>
    </div>
    <button class="btn btn-primary mt-3">Simpan</button>
</form>
<?php include __DIR__ . '/../layout/footer.php'; ?>
