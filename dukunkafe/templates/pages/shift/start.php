<?php

require_auth(['admin', 'kasir', 'manager']);

$title = 'Mulai Shift Kasir';
$pdo = get_pdo();

$branch_id = get_current_branch_id();

// Check if there is any active session in this branch
$active_branch_session_stmt = $pdo->prepare(
    "SELECT cs.id, cs.opened_at, u.name as cashier_name
     FROM cash_sessions cs
     JOIN users u ON u.id = cs.user_id
     WHERE cs.closed_at IS NULL AND (u.branch_id = ? OR u.branch_id IS NULL)
     ORDER BY cs.opened_at DESC
     LIMIT 1"
);
$active_branch_session_stmt->execute([$branch_id]);
$active_session = $active_branch_session_stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'start_session') {
        if ($active_session) {
            redirect(base_url('pos'));
        }
        $opening_cash = (float)($_POST['opening_cash'] ?? 0);
        if ($opening_cash < 0) { $opening_cash = 0; }

        $user_id = (int)($_SESSION['user']['id'] ?? 0);
        if ($user_id > 0) {
            $stmt = $pdo->prepare("INSERT INTO cash_sessions (user_id, opened_at, opening_cash) VALUES (?, NOW(), ?)");
            $stmt->execute([$user_id, $opening_cash]);
        }
        // After starting, go to POS
        redirect(base_url('pos'));
    }
}

view('shift/start', [
    'title' => $title,
    'active_session' => $active_session,
]);

