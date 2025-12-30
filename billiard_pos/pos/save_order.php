<?php
require_once __DIR__ . '/../includes/functions.php';
check_login();

$table_id = (int)($_POST['table_id'] ?? 0);
$cartKey = $table_id ?: 0;
$cart = $_SESSION['cart'][$cartKey] ?? [];

if (!$cart) {
    $_SESSION['flash_error'] = 'Cart kosong.';
    header("Location: pos.php?table_id={$table_id}");
    exit;
}

$payment_amount = (int)($_POST['payment_amount'] ?? 0);
$payment_method = $_POST['payment_method'] ?? 'cash';
$note = trim($_POST['note'] ?? '');
$customer_name = trim($_POST['customer_name'] ?? '');
$customer_phone = trim($_POST['customer_phone'] ?? '');
$pay_later = isset($_POST['pay_later']) ? 1 : 0;
$extra_charge_amount = max(0, (int)($_POST['extra_charge_amount'] ?? 0));
$extra_charge_note = trim($_POST['extra_charge_note'] ?? '');

$subtotal = 0;
$total_items = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['qty'];
    $total_items += $item['qty'];
}

$grand_total = $subtotal + $extra_charge_amount;

$running = $table_id ? get_running_session($pdo, $table_id) : null;
$session_id = $running['id'] ?? null;
$session_customer = $running['customer_name'] ?? null;
$session_phone = $running['customer_phone'] ?? null;
$final_customer = $session_customer ?: ($customer_name ?: null);
$final_phone = $session_phone ?: ($customer_phone ?: null);
$_SESSION['last_customer'][$cartKey] = $final_customer;
$_SESSION['last_customer_phone'][$cartKey] = $final_phone;
$selected_member_id = $_SESSION['selected_member'][$cartKey] ?? null;
$member = null;
if ($selected_member_id) {
    $mstmt = $pdo->prepare("SELECT * FROM members WHERE id = ? AND is_active = 1");
    $mstmt->execute([$selected_member_id]);
    $member = $mstmt->fetch();
}
$discount_amount = 0;
if ($member && !$pay_later) {
    $discount_amount = (int)floor($grand_total * ($member['discount_percent'] / 100));
}
$final_pay_total = $grand_total - $discount_amount;

$change = 0;
if ($pay_later) {
    $payment_amount = 0;
    $payment_method = 'tab';
    $is_paid = 0;
} else {
    if ($payment_amount < $final_pay_total) {
        $_SESSION['flash_error'] = 'Nominal bayar kurang.';
        header("Location: pos.php?table_id={$table_id}");
        exit;
    }
    $change = $payment_amount - $final_pay_total;
    $is_paid = 1;
}

$points_earned = 0;
if ($member && !$pay_later) {
    $points_earned = calculate_points($final_pay_total);
}

// cek stok
foreach ($cart as $pid => $item) {
    $stock = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
    $stock->execute([$pid]);
    $rowStock = $stock->fetch();
    $available = (int)($rowStock['stock'] ?? 0);
    if ($available > 0 && $item['qty'] > $available) {
        $_SESSION['flash_error'] = 'Stok tidak cukup untuk ' . htmlspecialchars($item['name']);
        header("Location: pos.php?table_id={$table_id}");
        exit;
    }
}

$stmt = $pdo->prepare("INSERT INTO orders (table_id, session_id, member_id, customer_name, customer_phone, operator_id, order_time, total_items, subtotal, grand_total, discount_amount, extra_charge_amount, extra_charge_note, payment_amount, payment_method, change_amount, is_paid, points_earned, note) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
$stmt->execute([
    $table_id ?: null,
    $session_id,
    $member['id'] ?? null,
    $final_customer,
    $final_phone,
    $_SESSION['user']['id'],
    date('Y-m-d H:i:s'),
    $total_items,
    $subtotal,
    $final_pay_total,
    $discount_amount,
    $extra_charge_amount,
    $extra_charge_note,
    $pay_later ? 0 : $payment_amount,
    $payment_method,
    $pay_later ? 0 : ($payment_amount - $final_pay_total),
    $is_paid,
    $points_earned,
    $note
]);
$order_id = $pdo->lastInsertId();

if ($is_paid) {
    $account_id = get_account_id_for_payment($pdo, $payment_method);
    if ($account_id) {
        add_journal($pdo, $account_id, 'in', $payment_amount, 'Pembayaran POS #' . $order_id, 'order', $order_id);
    }
}

$itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, qty, price, subtotal) VALUES (?,?,?,?,?)");
foreach ($cart as $pid => $item) {
    $itemStmt->execute([$order_id, $pid, $item['qty'], $item['price'], $item['price'] * $item['qty']]);
    adjust_stock($pdo, $pid, -1 * $item['qty']);
}

unset($_SESSION['cart'][$cartKey]);
$_SESSION['flash_success'] = $pay_later
    ? 'Order disimpan ke billing (bayar di checkout).'
    : 'Order tersimpan. Kembalian: ' . format_rupiah($change);
if ($member && !$pay_later && $points_earned > 0) {
    $pdo->prepare("UPDATE members SET points = points + ? WHERE id = ?")->execute([$points_earned, $member['id']]);
    $_SESSION['flash_success'] .= " | Poin +{$points_earned}";
}
header("Location: pos.php?table_id={$table_id}");
exit;
