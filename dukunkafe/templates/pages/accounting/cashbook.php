
<?php

require_auth(['admin', 'manager']);

$title = 'Buku Kas';
$pdo = get_pdo();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_transaction') {
        $account_id = $_POST['account_id'] ?? null;
        $type = $_POST['type'] ?? 'expense';
        $amount = (float)($_POST['amount'] ?? 0);
        $memo = $_POST['memo'] ?? '';

        if ($account_id && $amount > 0 && $memo) {
            $stmt = $pdo->prepare("INSERT INTO cash_transactions (account_id, type, amount, memo) VALUES (?, ?, ?, ?)");
            $stmt->execute([$account_id, $type, $amount, $memo]);
            // Realtime update of materialized balance
            if ($type === 'income') {
                $pdo->prepare("UPDATE cash_accounts SET balance = balance + ? WHERE id = ?")->execute([$amount, $account_id]);
            } else {
                $pdo->prepare("UPDATE cash_accounts SET balance = balance - ? WHERE id = ?")->execute([$amount, $account_id]);
            }
        }
    } elseif ($action === 'transfer') {
        $from_account_id = $_POST['from_account_id'] ?? null;
        $to_account_id = $_POST['to_account_id'] ?? null;
        $amount = (float)($_POST['amount'] ?? 0);
        $memo = $_POST['memo'] ?? 'Transfer antar akun';

        if ($from_account_id && $to_account_id && $amount > 0) {
            $pdo->beginTransaction();
            try {
                // Add expense transaction to the from account
                $stmt = $pdo->prepare("INSERT INTO cash_transactions (account_id, type, amount, memo) VALUES (?, 'expense', ?, ?)");
                $stmt->execute([$from_account_id, $amount, $memo]);
                $pdo->prepare("UPDATE cash_accounts SET balance = balance - ? WHERE id = ?")->execute([$amount, $from_account_id]);

                // Add income transaction to the to account
                $stmt = $pdo->prepare("INSERT INTO cash_transactions (account_id, type, amount, memo) VALUES (?, 'income', ?, ?)");
                $stmt->execute([$to_account_id, $amount, $memo]);
                $pdo->prepare("UPDATE cash_accounts SET balance = balance + ? WHERE id = ?")->execute([$amount, $to_account_id]);

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                // Handle error
            }
        }
    }

    redirect(base_url('accounting/cashbook'));
}

// Get all cash accounts
$accounts = $pdo->query("SELECT * FROM cash_accounts ORDER BY name")->fetchAll();

// Get all transactions
$transactions = $pdo->query("SELECT t.*, a.name as account_name FROM cash_transactions t JOIN cash_accounts a ON t.account_id = a.id ORDER BY t.created_at DESC")->fetchAll();

// Use materialized balances maintained in cash_accounts
$balances = [];
foreach ($accounts as $account) {
    $balances[$account['id']] = (float)$account['balance'];
}

view('accounting/cashbook', [
    'title' => $title,
    'accounts' => $accounts,
    'transactions' => $transactions,
    'balances' => $balances,
]);
