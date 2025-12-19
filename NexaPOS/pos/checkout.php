<?php
require_once __DIR__ . '/../middleware.php';
ensure_role(['admin','kasir']);
check_csrf();
$pdo = getPDO();

$money = function($v){
    $s = str_replace([' ', "\xC2\xA0"], '', (string)$v);
    $s = str_replace(',', '.', $s);
    $s = preg_replace('/\.(?=\d{3}(\D|$))/', '', $s);
    $n = (float)$s;
    return round($n);
};

// Cart dikirim dari front-end sebagai JSON (cart_json). Fallback ke session jika tidak ada.
$cartJson = $_POST['cart_json'] ?? '';
$cart = [];
if ($cartJson) {
    $cart = json_decode($cartJson, true) ?: [];
} else {
    $cart = $_SESSION['cart'] ?? [];
}
if (!$cart) {
    // Debug log
    log_error('Checkout cart empty | POST cart_json: ' . substr($cartJson,0,500) . ' | SESSION cart: ' . json_encode($_SESSION['cart'] ?? []));
    die('Cart kosong (debug dicatat di logs/app.log)');
}

$discountTotal = $money($_POST['discount_total']);
$tax = $money($_POST['tax']);
$payment = $_POST['payment_method'];
$cashPaid = $money($_POST['cash_paid']);
$customer = trim($_POST['customer_name'] ?? '');
$total = 0;
foreach ($cart as $c) $total += ($c['price'] * $c['qty']) - ($c['discount'] ?? 0);
$grand = $total - $discountTotal + $tax;
if ($grand < 0) $grand = 0;
if ($cashPaid < $grand && $payment === 'cash') die('Uang kurang');
$change = max(0, $cashPaid - $grand);

// Validasi stok tidak boleh minus
$stockStmt = $pdo->prepare("SELECT stock, name FROM items WHERE id=?");
foreach ($cart as $c) {
    $itemId = $c['id'] ?? null;
    if (!$itemId) continue;
    $stockStmt->execute([$itemId]);
    $row = $stockStmt->fetch();
    if (!$row) die('Barang tidak ditemukan');
    if ($row['stock'] < $c['qty']) {
        die('Stok tidak cukup untuk ' . htmlspecialchars($row['name']));
    }
}

$pdo->beginTransaction();
try {
    $saleNo = 'SL' . date('ymdHis');
    $pdo->prepare("INSERT INTO sales(sale_no,date,customer_name,total,discount,grand_total,payment_method,cash_paid,change_amount,created_by) VALUES(?,?,?,?,?,?,?,?,?,?)")
        ->execute([$saleNo, date('Y-m-d'), $customer, $total, $discountTotal, $grand, $payment, $cashPaid, $change, current_user()['id']]);
    $saleId = $pdo->lastInsertId();
    foreach ($cart as $c) {
        $itemId = $c['id'] ?? null;
        if (!$itemId) continue;
        $stmt = $pdo->prepare("INSERT INTO sale_items(sale_id,item_id,qty,price,discount,subtotal) VALUES(?,?,?,?,?,?)");
        $subtotal = ($c['price'] * $c['qty']) - ($c['discount'] ?? 0);
        $stmt->execute([$saleId,$itemId,$c['qty'],$c['price'],$c['discount'] ?? 0,$subtotal]);
        $pdo->prepare("UPDATE items SET stock = stock - ? WHERE id=?")->execute([$c['qty'],$itemId]);
        $pdo->prepare("INSERT INTO stock_moves(item_id, ref_type, ref_id, date, qty_in, qty_out, note, created_by) VALUES(?,?,?,?,?,?,?,?)")
            ->execute([$itemId,'sale',$saleId,date('Y-m-d'),0,$c['qty'],'Penjualan',current_user()['id']]);
    }
    // catat kas masuk
    $pdo->prepare("INSERT INTO cashbooks(date,type,amount,note,ref_type,ref_id,created_by) VALUES(?,?,?,?,?,?,?)")
        ->execute([date('Y-m-d'),'in',$grand,'Penjualan '.$saleNo,'sale',$saleId,current_user()['id']]);
    $pdo->commit();
    $_SESSION['cart'] = [];
    redirect('/pos/print.php?id=' . $saleId);
} catch (Exception $e) {
    $pdo->rollBack();
    log_error($e->getMessage());
    die('Gagal simpan transaksi');
}
?>
