<?php

require_auth(['admin', 'kasir']);

$title = 'Active Orders';
$pdo = get_pdo($config);

// Handle Payment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = $_POST['order_id'] ?? null;
    $payment_method = $_POST['payment_method'] ?? 'CASH';
    $paid_total = (float)($_POST['paid_total'] ?? 0);

    if ($order_id && $paid_total > 0) {
        try {
            $pdo->beginTransaction();

            // 1. Fetch order details
            $order_stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND status = 'OPEN'");
            $order_stmt->execute([$order_id]);
            $order = $order_stmt->fetch();

            if (!$order) {
                throw new Exception("Order not found or already closed.");
            }

            // 2. Update order status to CLOSED
            $update_stmt = $pdo->prepare("UPDATE orders SET status = 'CLOSED', payment_method = ?, paid_total = ?, closed_at = NOW() WHERE id = ?");
            $update_stmt->execute([$payment_method, $paid_total, $order_id]);

            // 3. Create Sales Journal Entry
            $acc_kas = 1; // Kas Toko
            $acc_penjualan = 3; // Penjualan
            $acc_pajak = 11; // Pajak Keluaran

            $journal_stmt = $pdo->prepare('INSERT INTO journals (date, ref_type, ref_id, memo) VALUES (CURDATE(), \'ORDER_PAYMENT\', ?, ?)');
            $journal_line_stmt = $pdo->prepare('INSERT INTO journal_lines (journal_id, account_id, debit, credit) VALUES (?, ?, ?, ?)');
            
            $journal_stmt->execute([$order_id, 'Pembayaran untuk ' . $order['order_no']]);
            $journal_id = $pdo->lastInsertId();

            $journal_line_stmt->execute([$journal_id, $acc_kas, $order['total'], 0]); // Dr. Kas
            $journal_line_stmt->execute([$journal_id, $acc_penjualan, 0, $order['subtotal'] + $order['service']]); // Cr. Penjualan
            $journal_line_stmt->execute([$journal_id, $acc_pajak, 0, $order['tax']]); // Cr. Pajak

            $pdo->commit();

        } catch (Exception $e) {
            $pdo->rollBack();
            die("Failed to process payment: " . $e->getMessage());
        }
    }
            redirect(base_url('receipt?id=' . $order_id));
}


// Fetch all OPEN orders (current branch)
$bid = get_current_branch_id();
$stmt = $pdo->prepare("SELECT o.*, t.name as table_name 
    FROM orders o 
    LEFT JOIN tables t ON o.table_id = t.id 
    WHERE o.status = 'OPEN' AND o.branch_id = ? 
    ORDER BY o.created_at DESC");
$stmt->execute([$bid]);
$open_orders = $stmt->fetchAll();

view('orders', [
    'title' => $title,
    'open_orders' => $open_orders,
]);