<?php require_once __DIR__ . '/../helpers.php'; ?>
<?php $user = current_user(); ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title><?= APP_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL; ?>/assets/style.css">
    <style>[x-cloak]{display:none!important;}</style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-3">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= BASE_URL; ?>/index.php"><i class="bi bi-bag-check"></i> <?= APP_NAME; ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL; ?>/pos/index.php"><i class="bi bi-cash-stack"></i> POS</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL; ?>/pos/sales.php"><i class="bi bi-receipt"></i> Riwayat</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL; ?>/transaksi/pembelian_index.php"><i class="bi bi-cart4"></i> Pembelian</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"><i class="bi bi-cash-coin"></i> Keuangan</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= BASE_URL; ?>/keuangan/cashbook.php">Buku Kas</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL; ?>/keuangan/salary.php">Gaji</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL; ?>/laporan/cashflow.php">Cash Flow</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL; ?>/laporan/laba_rugi.php">Laba Rugi</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"><i class="bi bi-database"></i> Master</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= BASE_URL; ?>/master/barang_index.php">Barang</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL; ?>/master/kategori_index.php">Kategori</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL; ?>/master/keyword_kategori.php">Keyword Kategori</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL; ?>/master/supplier_index.php">Supplier</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL; ?>/master/user_index.php">User</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"><i class="bi bi-graph-up"></i> Laporan</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= BASE_URL; ?>/laporan/penjualan.php">Penjualan</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL; ?>/laporan/pembelian.php">Pembelian</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL; ?>/laporan/stok.php">Stok</a></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL; ?>/laporan/laba_kotor.php">Laba Kotor</a></li>
                    </ul>
                </li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL; ?>/backup.php"><i class="bi bi-hdd-network"></i> Backup</a></li>
            </ul>
            <div class="text-white small">
                <i class="bi bi-person-circle"></i> <?= htmlspecialchars($user['name'] ?? ''); ?> (<?= htmlspecialchars($user['role'] ?? ''); ?>)
                | <a class="text-white" href="<?= BASE_URL; ?>/auth/logout.php">Logout</a>
            </div>
        </div>
    </div>
</nav>
<div class="container mb-5">
