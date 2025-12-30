<?php

require_auth(['admin', 'manager', 'kasir']);

$title = 'Rekap Kas & Tutup Shift';
$pdo = get_pdo($config);

$user_id = $_SESSION['user']['id'];

// Handle closing session from this page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'end_session') {
    $closing_cash = (float)($_POST['closing_cash'] ?? 0);
    $sess_stmt = $pdo->prepare("SELECT * FROM cash_sessions WHERE user_id = ? AND closed_at IS NULL ORDER BY opened_at DESC LIMIT 1");
    $sess_stmt->execute([$user_id]);
    $active_session = $sess_stmt->fetch();
    if ($active_session) {
        $upd = $pdo->prepare("UPDATE cash_sessions SET closed_at = NOW(), closing_cash = ? WHERE id = ?");
        $upd->execute([$closing_cash, $active_session['id']]);

        // Get sales by payment method for the session (prefer closed_at and filter by branch if present)
        $startAt = $active_session['opened_at'];
        $endAt = date('Y-m-d H:i:s');
        $branch_id = get_current_branch_id();
        try {
            $sales_stmt = $pdo->prepare("SELECT COALESCE(payment_method,'UNKNOWN') as method, SUM(paid_total) as total
                FROM orders 
                WHERE status = 'CLOSED' AND closed_at BETWEEN ? AND ? AND (branch_id = ?)
                GROUP BY COALESCE(payment_method,'UNKNOWN')");
            $sales_stmt->execute([$startAt, $endAt, $branch_id]);
        } catch (Exception $e) {
            // Fallback for DBs without branch_id or closed_at
            $sales_stmt = $pdo->prepare("SELECT COALESCE(payment_method,'UNKNOWN') as method, SUM(paid_total) as total
                FROM orders 
                WHERE status = 'CLOSED' AND created_at BETWEEN ? AND ?
                GROUP BY COALESCE(payment_method,'UNKNOWN')");
            $sales_stmt->execute([$startAt, $endAt]);
        }
        $sales = $sales_stmt->fetchAll();

        // Get payment method mappings
        $mappings_stmt = $pdo->query("SELECT * FROM payment_method_mappings");
        $mappings = [];
        while ($row = $mappings_stmt->fetch()) {
            $mappings[$row['payment_method']] = $row['account_id'];
        }

        // Create transactions for each payment method
        foreach ($sales as $sale) {
            $method = strtoupper($sale['method']);
            $amount = (float)$sale['total'];
            if (isset($mappings[$method]) && $amount > 0) {
                $account_id = $mappings[$method];
                $stmt = $pdo->prepare("INSERT INTO cash_transactions (account_id, type, amount, memo) VALUES (?, 'income', ?, ?)");
                $stmt->execute([$account_id, $amount, 'Penjualan ' . $method]);
                // Realtime update materialized balance
                $pdo->prepare("UPDATE cash_accounts SET balance = balance + ? WHERE id = ?")->execute([$amount, $account_id]);
            }
        }
    }
    redirect(base_url('end_of_day'));
}

// Active session (if any)
$sess_stmt = $pdo->prepare("SELECT * FROM cash_sessions WHERE user_id = ? AND closed_at IS NULL ORDER BY opened_at DESC LIMIT 1");
$sess_stmt->execute([$user_id]);
$active_session = $sess_stmt->fetch();

if ($active_session) {
    $start = $active_session['opened_at'];
    $end = date('Y-m-d H:i:s');
} else {
    // No active session: default to today
    $start = date('Y-m-d 00:00:00');
    $end = date('Y-m-d 23:59:59');
}

// Sales by payment method
$branch_id = get_current_branch_id();
try {
    $sales_stmt = $pdo->prepare("SELECT COALESCE(payment_method,'UNKNOWN') as method, SUM(paid_total) as total
        FROM orders 
        WHERE status = 'CLOSED' AND closed_at BETWEEN ? AND ? AND (branch_id = ?)
        GROUP BY COALESCE(payment_method,'UNKNOWN')");
    $sales_stmt->execute([$start, $end, $branch_id]);
} catch (Exception $e) {
    // Fallback for DBs without branch_id or closed_at
    $sales_stmt = $pdo->prepare("SELECT COALESCE(payment_method,'UNKNOWN') as method, SUM(paid_total) as total
        FROM orders 
        WHERE status = 'CLOSED' AND created_at BETWEEN ? AND ?
        GROUP BY COALESCE(payment_method,'UNKNOWN')");
    $sales_stmt->execute([$start, $end]);
}
$sales = $sales_stmt->fetchAll();

$by_method = ['CASH'=>0,'QRIS'=>0,'CARD'=>0,'OTHER'=>0];
foreach ($sales as $row) {
    $m = strtoupper($row['method']);
    if (!isset($by_method[$m])) { $by_method['OTHER'] += (float)$row['total']; }
    else { $by_method[$m] += (float)$row['total']; }
}

$opening_cash = (float)($active_session['opening_cash'] ?? 0);
$cash_sales = (float)$by_method['CASH'];

// Compute total cash expenses during period from cash_transactions for cash-type accounts
$total_expenses = 0.0;
try {
    $cash_acc_ids = $pdo->query("SELECT id FROM cash_accounts WHERE type = 'cash'")->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($cash_acc_ids)) {
        $in = implode(',', array_map('intval', $cash_acc_ids));
        $stmt = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM cash_transactions WHERE type='expense' AND account_id IN ($in) AND created_at BETWEEN '" . $start . "' AND '" . $end . "'");
        $total_expenses = (float)$stmt->fetchColumn();
    }
} catch (Exception $e) { /* ignore */ }

$expected_cash = $opening_cash + $cash_sales - $total_expenses;

view('end_of_day', compact('title','active_session','start','end','by_method','opening_cash','cash_sales','expected_cash'));
