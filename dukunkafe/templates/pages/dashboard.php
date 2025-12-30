<?php

require_auth(); // User must be logged in to see this page

$title = 'Dashboard';

$pdo = get_pdo($config);
$branch_id = get_current_branch_id();

// Helper to run a query with optional branch filter; falls back if column is missing
function q_scalar_with_branch(PDO $pdo, string $baseSql, int $branchId)
{
    try {
        $stmt = $pdo->query(str_replace('{BRANCH}', ' AND (branch_id = ' . (int)$branchId . ')', $baseSql));
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        $stmt = $pdo->query(str_replace('{BRANCH}', '', $baseSql));
        return $stmt->fetchColumn();
    }
}

// --- Daily Metrics (Today) ---
$today_revenue = (float) q_scalar_with_branch($pdo, "SELECT COALESCE(SUM(total),0) FROM orders WHERE status='CLOSED' AND DATE(closed_at)=CURDATE(){BRANCH}", $branch_id);
$today_orders_closed = (int) q_scalar_with_branch($pdo, "SELECT COUNT(*) FROM orders WHERE status='CLOSED' AND DATE(closed_at)=CURDATE(){BRANCH}", $branch_id);
$today_orders_created = (int) q_scalar_with_branch($pdo, "SELECT COUNT(*) FROM orders WHERE DATE(created_at)=CURDATE(){BRANCH}", $branch_id);

// Active metrics
$active_orders = (int) q_scalar_with_branch($pdo, "SELECT COUNT(*) FROM orders WHERE status='OPEN'{BRANCH}", $branch_id);
$active_tables = (int) q_scalar_with_branch($pdo, "SELECT COUNT(DISTINCT table_id) FROM orders WHERE status='OPEN' AND table_id IS NOT NULL{BRANCH}", $branch_id);

// --- Weekly Metrics (ISO Week) ---
$week_revenue = (float) q_scalar_with_branch($pdo, "SELECT COALESCE(SUM(total),0) FROM orders WHERE status='CLOSED' AND YEARWEEK(closed_at,1)=YEARWEEK(CURDATE(),1){BRANCH}", $branch_id);
$week_orders_closed = (int) q_scalar_with_branch($pdo, "SELECT COUNT(*) FROM orders WHERE status='CLOSED' AND YEARWEEK(closed_at,1)=YEARWEEK(CURDATE(),1){BRANCH}", $branch_id);

// --- Yesterday for comparison ---
$yesterday_revenue = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status='CLOSED' AND DATE(closed_at)=DATE_SUB(CURDATE(), INTERVAL 1 DAY)")
    ->fetchColumn();
$yesterday_orders_closed = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='CLOSED' AND DATE(closed_at)=DATE_SUB(CURDATE(), INTERVAL 1 DAY)")
    ->fetchColumn();

// --- AOV calculations ---
$aov_today = $today_orders_closed > 0 ? ($today_revenue / $today_orders_closed) : 0;
$aov_week = $week_orders_closed > 0 ? ($week_revenue / $week_orders_closed) : 0;

// --- Delta/Change Today vs Yesterday ---
$rev_delta_abs = $today_revenue - $yesterday_revenue;
$rev_delta_pct = $yesterday_revenue > 0 ? ($rev_delta_abs / $yesterday_revenue) * 100 : null;
$orders_delta_abs = $today_orders_closed - $yesterday_orders_closed;
$orders_delta_pct = $yesterday_orders_closed > 0 ? ($orders_delta_abs / $yesterday_orders_closed) * 100 : null;

// --- Trends: Last 7 days revenue and order counts ---
try {
    $trend_stmt = $pdo->query("SELECT DATE(closed_at) d, COALESCE(SUM(total),0) rev, COUNT(*) cnt
        FROM orders
        WHERE status='CLOSED' AND closed_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND (branch_id = $branch_id)
        GROUP BY DATE(closed_at)
        ORDER BY d ASC");
} catch (Exception $e) {
    $trend_stmt = $pdo->query("SELECT DATE(closed_at) d, COALESCE(SUM(total),0) rev, COUNT(*) cnt
        FROM orders
        WHERE status='CLOSED' AND closed_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(closed_at)
        ORDER BY d ASC");
}
$rows = $trend_stmt->fetchAll(PDO::FETCH_ASSOC);

// Build 7-day series including zeros for missing days
$daily_trend = [];
for ($i=6; $i>=0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} day"));
    $daily_trend[$date] = ['rev' => 0, 'cnt' => 0];
}
foreach ($rows as $r) {
    $d = $r['d'];
    if (isset($daily_trend[$d])) {
        $daily_trend[$d] = ['rev' => (float)$r['rev'], 'cnt' => (int)$r['cnt']];
    }
}

$max_rev = max(array_map(fn($x)=>$x['rev'], $daily_trend)) ?: 1;
$max_cnt = max(array_map(fn($x)=>$x['cnt'], $daily_trend)) ?: 1;

// Prepare data for view
$cards = [
    ['label' => 'Omzet Hari Ini', 'value' => 'Rp ' . number_format($today_revenue, 0, ',', '.')],
    ['label' => 'Order Selesai Hari Ini', 'value' => number_format($today_orders_closed, 0, ',', '.')],
    ['label' => 'Order Dibuat Hari Ini', 'value' => number_format($today_orders_created, 0, ',', '.')],
    ['label' => 'Omzet Minggu Ini', 'value' => 'Rp ' . number_format($week_revenue, 0, ',', '.')],
    ['label' => 'Order Minggu Ini', 'value' => number_format($week_orders_closed, 0, ',', '.')],
    ['label' => 'Pelanggan Aktif (Order OPEN)', 'value' => number_format($active_orders, 0, ',', '.')],
    ['label' => 'Meja Aktif (Dine-in)', 'value' => number_format($active_tables, 0, ',', '.')],
];

// --- Top 5 Menus Today (by revenue) ---
try {
    $top_stmt = $pdo->query("SELECT m.name, SUM(oi.qty) qty, SUM(oi.qty*oi.price) total
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        JOIN menus m ON oi.menu_id = m.id
        WHERE o.status='CLOSED' AND DATE(o.closed_at)=CURDATE() AND (o.branch_id = $branch_id)
        GROUP BY m.id, m.name
        ORDER BY total DESC
        LIMIT 5");
} catch (Exception $e) {
    $top_stmt = $pdo->query("SELECT m.name, SUM(oi.qty) qty, SUM(oi.qty*oi.price) total
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        JOIN menus m ON oi.menu_id = m.id
        WHERE o.status='CLOSED' AND DATE(o.closed_at)=CURDATE()
        GROUP BY m.id, m.name
        ORDER BY total DESC
        LIMIT 5");
}
$top_menus_today = $top_stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Top 5 Categories Today (by revenue) ---
$top_cat_stmt = $pdo->query("SELECT c.name, SUM(oi.qty) qty, SUM(oi.qty*oi.price) total
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.id
    JOIN menus m ON oi.menu_id = m.id
    JOIN categories c ON m.category_id = c.id
    WHERE o.status='CLOSED' AND DATE(o.closed_at)=CURDATE()
    GROUP BY c.id, c.name
    ORDER BY total DESC
    LIMIT 5");
$top_categories_today = $top_cat_stmt->fetchAll(PDO::FETCH_ASSOC);

// --- AOV per Channel (Today) ---
try {
    $ch_stmt = $pdo->query("SELECT channel, COALESCE(SUM(total),0) rev, COUNT(*) cnt
        FROM orders
        WHERE status='CLOSED' AND DATE(closed_at)=CURDATE() AND branch_id = $branch_id
        GROUP BY channel");
} catch (Exception $e) {
    $ch_stmt = $pdo->query("SELECT channel, COALESCE(SUM(total),0) rev, COUNT(*) cnt
        FROM orders
        WHERE status='CLOSED' AND DATE(closed_at)=CURDATE()
        GROUP BY channel");
}
$rows = $ch_stmt->fetchAll(PDO::FETCH_ASSOC);
$aov_by_channel_today = [];
foreach ($rows as $r) {
    $cnt = (int)$r['cnt'];
    $rev = (float)$r['rev'];
    $aov_by_channel_today[$r['channel']] = [
        'rev' => $rev,
        'cnt' => $cnt,
        'aov' => $cnt > 0 ? ($rev / $cnt) : 0,
    ];
}

// --- Per-branch overview (today)
$branches = [];
try {
    $has_br = $pdo->query("SHOW TABLES LIKE 'branches'")->fetchColumn();
    if ($has_br) {
        $branches = $pdo->query("SELECT b.id, b.name,
            COALESCE((SELECT SUM(o.total) FROM orders o WHERE o.branch_id=b.id AND o.status='CLOSED' AND DATE(o.closed_at)=CURDATE()),0) AS rev_today,
            COALESCE((SELECT COUNT(*) FROM orders o WHERE o.branch_id=b.id AND o.status='CLOSED' AND DATE(o.closed_at)=CURDATE()),0) AS orders_today
            FROM branches b WHERE b.active=1 ORDER BY b.name")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {}

view('dashboard', [
    'title' => $title,
    'cards' => $cards,
    'daily_trend' => $daily_trend,
    'max_rev' => $max_rev,
    'max_cnt' => $max_cnt,
    'aov_today' => $aov_today,
    'aov_week' => $aov_week,
    'rev_delta_abs' => $rev_delta_abs,
    'rev_delta_pct' => $rev_delta_pct,
    'orders_delta_abs' => $orders_delta_abs,
    'orders_delta_pct' => $orders_delta_pct,
    'top_menus_today' => $top_menus_today,
    'top_categories_today' => $top_categories_today,
    'aov_by_channel_today' => $aov_by_channel_today,
    'branches_overview' => $branches,
]);
