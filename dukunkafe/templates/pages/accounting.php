<?php

require_auth();

$title = 'Buku Kas & Akun';

$pdo = get_pdo($config);

// 1. Fetch all cash accounts
$stmt = $pdo->prepare("SELECT id, name, type FROM cash_accounts ORDER BY name");
$stmt->execute();
$accounts = $stmt->fetchAll();

view('accounting', [
    'title' => $title,
    'accounts' => $accounts
]);
