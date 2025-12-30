<?php
require_once __DIR__ . '/functions.php';
$company = load_company_settings();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Billiard POS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/billiard_pos/assets/css/custom.css">
</head>
<body class="text-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-black shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="/billiard_pos/index.php">
            <?php if (!empty($company['logo']) && file_exists(__DIR__ . '/../' . ltrim($company['logo'], '/'))): ?>
                <img src="/billiard_pos/<?php echo ltrim($company['logo'], '/'); ?>" alt="Logo" style="height:28px;">
            <?php endif; ?>
            <span><?php echo htmlspecialchars($company['name'] ?? 'Billiard POS'); ?></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link <?php echo active_menu('/index.php'); ?>" href="/billiard_pos/index.php">Dashboard</a></li>
                <?php if (!empty($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo active_menu('/tables/'); ?> <?php echo active_menu('/master/'); ?>" href="#" role="button" data-bs-toggle="dropdown">Master Data</a>
                        <ul class="dropdown-menu dropdown-menu-dark">
                            <li><a class="dropdown-item" href="/billiard_pos/tables/list.php">Meja</a></li>
                            <li><a class="dropdown-item" href="/billiard_pos/master/tariffs.php">Tarif</a></li>
                            <li><a class="dropdown-item" href="/billiard_pos/master/packages.php">Paket Promo</a></li>
                            <li><a class="dropdown-item" href="/billiard_pos/master/products.php">Produk</a></li>
                            <li><a class="dropdown-item" href="/billiard_pos/master/members.php">Member</a></li>
                            <li><a class="dropdown-item" href="/billiard_pos/master/users.php">User</a></li>
                            <li><a class="dropdown-item" href="/billiard_pos/master/company.php">Perusahaan</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/billiard_pos/inventory/purchase_create.php">Pembelian</a></li>
                            <li><a class="dropdown-item" href="/billiard_pos/inventory/purchase_list.php">Riwayat Pembelian</a></li>
                            <li><a class="dropdown-item" href="/billiard_pos/inventory/expenses.php">Pengeluaran</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
                <?php if (!empty($_SESSION['user'])): ?>
                    <li class="nav-item"><a class="nav-link <?php echo active_menu('/pos/pos.php'); ?>" href="/billiard_pos/pos/pos.php">POS</a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo active_menu('/reports/'); ?>" href="#" role="button" data-bs-toggle="dropdown">Laporan</a>
                        <ul class="dropdown-menu dropdown-menu-dark">
                            <li><a class="dropdown-item" href="/billiard_pos/reports/report_daily.php">Harian</a></li>
                            <li><a class="dropdown-item" href="/billiard_pos/reports/shift_control.php">Shift / Kas</a></li>
                            <li><a class="dropdown-item" href="/billiard_pos/reports/report_shift.php">Rekap Kas Shift</a></li>
                            <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                                <li><a class="dropdown-item" href="/billiard_pos/reports/report_range.php">Range</a></li>
                                <li><a class="dropdown-item" href="/billiard_pos/reports/report_profit.php">Laba Rugi</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
            <div class="d-flex align-items-center">
                <?php if (!empty($_SESSION['user'])): ?>
                    <?php $u = $_SESSION['user']; ?>
                    <span class="me-3 text-info">
                        <?php echo htmlspecialchars($u['username'] ?? ''); ?>
                        <?php if (!empty($u['role'])): ?> (<?php echo htmlspecialchars($u['role']); ?>)<?php endif; ?>
                    </span>
                    <a class="btn btn-outline-light btn-sm" href="/billiard_pos/auth/logout.php">Logout</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<script>window.APP_ROLE = "<?php echo $_SESSION['user']['role'] ?? ''; ?>";</script>
<main class="container py-4">
