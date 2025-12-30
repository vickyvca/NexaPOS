<?php

require_auth(['admin','manager','kasir','waiter','kitchen','hr']);

$id = isset($_POST['branch_id']) ? (int)$_POST['branch_id'] : (int)($_GET['branch_id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    // For API-like behavior, return JSON, otherwise just fail.
    if (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid branch id']);
    }
    exit;
}

set_current_branch_id($id);

// Redirect back to the previous page, or to dashboard as a fallback.
$redirect_url = $_SERVER['HTTP_REFERER'] ?? base_url('dashboard');
redirect($redirect_url);
