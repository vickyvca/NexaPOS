<?php
require_once __DIR__ . '/middleware.php';
$pdo = getPDO();
$today = date('Y-m-d');
$monthStart = date('Y-m-01');

$omzetToday = $pdo->prepare("SELECT COALESCE(SUM(grand_total),0) FROM sales WHERE date = ?");
$omzetToday->execute([$today]);
$omzet = $omzetToday->fetchColumn();

$omzetMonth = $pdo->prepare("SELECT COALESCE(SUM(grand_total),0) FROM sales WHERE date BETWEEN ? AND ?");
$omzetMonth->execute([$monthStart, $today]);
$omzetM = $omzetMonth->fetchColumn();

$trxToday = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE date = ?");
$trxToday->execute([$today]);
$trxCount = $trxToday->fetchColumn();

$purchasesToday = $pdo->prepare("SELECT COALESCE(SUM(total),0) FROM purchases WHERE date = ? AND status='posted'");
$purchasesToday->execute([$today]);
$buyToday = $purchasesToday->fetchColumn();

// laba kotor hari ini
$labaStmt = $pdo->prepare("SELECT SUM((si.price - i.buy_price) * si.qty) FROM sale_items si JOIN sales s ON s.id=si.sale_id JOIN items i ON i.id=si.item_id WHERE s.date = ?");
$labaStmt->execute([$today]);
$grossToday = $labaStmt->fetchColumn() ?: 0;

// low stock count
$lowCount = (int)$pdo->query("SELECT COUNT(*) FROM items WHERE stock <= min_stock")->fetchColumn();

// Chart data 7 hari
$start7 = date('Y-m-d', strtotime('-6 days'));
$dates = [];
for ($i=6;$i>=0;$i--) { $dates[] = date('Y-m-d', strtotime("-$i days")); }
$salesMap = array_fill_keys($dates, 0);
$purchaseMap = array_fill_keys($dates, 0);
$rows = $pdo->prepare("SELECT date, SUM(grand_total) t FROM sales WHERE date BETWEEN ? AND ? GROUP BY date");
$rows->execute([$start7, $today]);
foreach ($rows as $r) { $salesMap[$r['date']] = (float)$r['t']; }
$rows2 = $pdo->prepare("SELECT date, SUM(total) t FROM purchases WHERE status='posted' AND date BETWEEN ? AND ? GROUP BY date");
$rows2->execute([$start7, $today]);
foreach ($rows2 as $r) { $purchaseMap[$r['date']] = (float)$r['t']; }

$topItems = $pdo->query("SELECT i.name, SUM(si.qty) qty FROM sale_items si JOIN items i ON i.id=si.item_id GROUP BY si.item_id ORDER BY qty DESC LIMIT 5")->fetchAll();
$lowStock = $pdo->query("SELECT name, stock, min_stock FROM items WHERE stock <= min_stock ORDER BY stock ASC LIMIT 5")->fetchAll();
?>
<?php include __DIR__ . '/layout/header.php'; ?>
<h4 class="mb-3"><i class="bi bi-speedometer2"></i> Dashboard</h4>
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card kpi-card">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="small">Omzet Hari Ini</div>
                    <div class="h5 mb-0"><?= format_rupiah($omzet); ?></div>
                </div>
                <div class="icon"><i class="bi bi-cash-coin"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card kpi-card" style="background:linear-gradient(135deg,#0ea5e9,#22c55e);">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="small">Omzet Bulan Ini</div>
                    <div class="h5 mb-0"><?= format_rupiah($omzetM); ?></div>
                </div>
                <div class="icon"><i class="bi bi-calendar-event"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card kpi-card" style="background:linear-gradient(135deg,#f97316,#facc15);">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="small">Laba Kotor Hari Ini</div>
                    <div class="h5 mb-0"><?= format_rupiah($grossToday); ?></div>
                </div>
                <div class="icon"><i class="bi bi-graph-up-arrow"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card kpi-card" style="background:linear-gradient(135deg,#8b5cf6,#6366f1);">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <div class="small">Trx Hari Ini</div>
                    <div class="h5 mb-0"><?= $trxCount; ?> trx</div>
                    <div class="small">Pembelian: <?= format_rupiah($buyToday); ?></div>
                </div>
                <div class="icon"><i class="bi bi-cart-check"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card chart-card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="h6 mb-0">Tren 7 Hari</div>
                    <span class="small text-muted">Penjualan vs Pembelian</span>
                </div>
                <canvas id="chart7"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card chart-card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="h6 mb-0">Stok Menipis</div>
                    <span class="badge text-bg-danger"><?= $lowCount; ?> item</span>
                </div>
                <table class="table table-sm table-borderless">
                    <?php foreach ($lowStock as $l): ?>
                        <tr><td><?= htmlspecialchars($l['name']); ?></td><td class="text-end"><?= $l['stock']; ?>/<?= $l['min_stock']; ?></td></tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
        <div class="card chart-card">
            <div class="card-body">
                <div class="h6 mb-2">Top Item</div>
                <table class="table table-sm table-borderless">
                    <?php foreach ($topItems as $t): ?>
                        <tr><td><?= htmlspecialchars($t['name']); ?></td><td class="text-end"><?= $t['qty']; ?></td></tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
const ctx = document.getElementById('chart7');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_map(fn($d)=>date('d M', strtotime($d)), $dates)); ?>,
        datasets: [
            {label:'Penjualan', data: <?= json_encode(array_values($salesMap)); ?>, borderColor:'#22c55e', backgroundColor:'rgba(34,197,94,.2)', tension:.3, fill:true},
            {label:'Pembelian', data: <?= json_encode(array_values($purchaseMap)); ?>, borderColor:'#60a5fa', backgroundColor:'rgba(96,165,250,.2)', tension:.3, fill:true}
        ]
    },
    options: {plugins:{legend:{labels:{color:'#e5e7eb'}}}, scales:{x:{ticks:{color:'#e5e7eb'}}, y:{ticks:{color:'#e5e7eb'}}}}
});
</script>
<?php include __DIR__ . '/layout/footer.php'; ?>
