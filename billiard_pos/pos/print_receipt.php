<?php
require_once __DIR__ . '/../includes/functions.php';
check_login();
$company = load_company_settings();

$order_id = (int)($_GET['order_id'] ?? 0);
if (!$order_id) {
    http_response_code(400);
    echo "order_id wajib";
    exit;
}

$stmt = $pdo->prepare("SELECT o.*, t.name AS table_name, s.start_time, s.end_time, s.total_minutes, s.total_amount, s.customer_name, s.customer_phone, u.username AS operator_name
    FROM orders o
    LEFT JOIN billiard_tables t ON o.table_id = t.id
    LEFT JOIN sessions s ON o.session_id = s.id
    LEFT JOIN users u ON o.operator_id = u.id
    WHERE o.id = ? LIMIT 1");
$stmt->execute([$order_id]);
$order = $stmt->fetch();
if (!$order) {
    http_response_code(404);
    echo "Order tidak ditemukan.";
    exit;
}

$items = $pdo->prepare("SELECT oi.*, p.name AS product_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$items->execute([$order_id]);
$items = $items->fetchAll();

$billing_amount = (int)($order['grand_total'] - $order['subtotal'] - $order['extra_charge_amount'] + $order['discount_amount']);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt #<?php echo $order_id; ?></title>
    <style>
        /* Print-only layout 80mm */
        @page { size: 80mm auto; margin: 0; }
        html, body {
            width: 80mm;
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
        }
    body { font-family: "Courier New", monospace; font-size: 15px; font-weight: 800; line-height: 1.35; }
    #receipt {
        width: 80mm;
        margin: 0;
        padding: 1mm 0.5mm 0 0.5mm;
        box-sizing: border-box;
    }
        #receipt * { box-sizing: border-box; }
    .title { font-weight: 900; font-size: 18px; text-align: center; }
    .center { text-align: center; }
    .sep { border-top: 1px dashed #000; margin: 3px 0; }
    .row { display: flex; justify-content: space-between; }
    .bold { font-weight: 900; }
    img.logo { max-width: 100%; height: auto; margin: 0 auto 4px auto; display: block; }
    .small { font-size: 12px; font-weight: 700; }
        @media print {
            body * { visibility: hidden; }
            #receipt, #receipt * { visibility: visible; }
            #receipt { position: absolute; left: 0; top: 0; }
        }
    </style>
</head>
<body>
<div id="receipt">
    <div class="center">
        <?php if (!empty($company['logo']) && file_exists(__DIR__ . '/../' . ltrim($company['logo'], '/'))): ?>
            <img class="logo" src="/billiard_pos/<?php echo ltrim($company['logo'], '/'); ?>" alt="Logo">
        <?php endif; ?>
        <div class="title"><?php echo htmlspecialchars($company['name'] ?? 'Billiard POS'); ?></div>
        <?php if (!empty($company['address'])): ?><div class="small"><?php echo htmlspecialchars($company['address']); ?></div><?php endif; ?>
        <?php if (!empty($company['phone'])): ?><div class="small">Telp: <?php echo htmlspecialchars($company['phone']); ?></div><?php endif; ?>
    </div>
    <div class="sep"></div>
    <div>Invoice: #<?php echo $order_id; ?></div>
    <div class="small"><?php echo format_datetime($order['order_time']); ?></div>
    <div>Meja: <?php echo htmlspecialchars($order['table_name'] ?? ($order['table_id'] ?: '-')); ?></div>
    <div>Kasir: <?php echo htmlspecialchars($order['operator_name'] ?? '-'); ?></div>
    <div>Customer: <?php echo htmlspecialchars($order['customer_name'] ?? '-'); ?></div>
    <div>HP: <?php echo htmlspecialchars($order['customer_phone'] ?? '-'); ?></div>
    <div class="sep"></div>
    <?php if ($order['start_time']): ?>
        <div>Mulai : <?php echo format_datetime($order['start_time']); ?></div>
        <div>Selesai: <?php echo $order['end_time'] ? format_datetime($order['end_time']) : '-'; ?></div>
        <div class="row"><span>Durasi</span><span><?php echo human_duration((int)$order['total_minutes']); ?></span></div>
        <div class="row"><span>Billing</span><span><?php echo format_rupiah($order['total_amount']); ?></span></div>
        <div class="sep"></div>
    <?php endif; ?>
    <?php if ($items): ?>
        <div class="bold">Detail POS</div>
        <?php foreach ($items as $it): ?>
            <div class="row">
                <span><?php echo htmlspecialchars($it['product_name'] ?? ('Item #'.$it['product_id'])); ?> x <?php echo $it['qty']; ?></span>
                <span><?php echo format_rupiah($it['subtotal']); ?></span>
            </div>
        <?php endforeach; ?>
        <div class="sep"></div>
    <?php endif; ?>
    <?php if ($order['extra_charge_amount'] > 0): ?>
        <div class="row"><span>Extra Charge</span><span><?php echo format_rupiah($order['extra_charge_amount']); ?></span></div>
        <?php if ($order['extra_charge_note']): ?><div class="small"><?php echo htmlspecialchars($order['extra_charge_note']); ?></div><?php endif; ?>
    <?php endif; ?>
    <?php if ($order['discount_amount'] > 0): ?>
        <div class="row"><span>Diskon</span><span>-<?php echo format_rupiah($order['discount_amount']); ?></span></div>
    <?php endif; ?>
    <div class="row bold"><span>Grand Total</span><span><?php echo format_rupiah($order['grand_total']); ?></span></div>
    <div class="row"><span>Bayar</span><span><?php echo format_rupiah($order['payment_amount']); ?></span></div>
    <div class="row"><span>Kembali</span><span><?php echo format_rupiah($order['change_amount']); ?></span></div>
    <div class="center small" style="margin-top:6px;">Terima kasih</div>
</div>
<script>
// auto print jika dibuka langsung, lalu tutup otomatis
window.onload = function() {
    window.print();
    setTimeout(function(){ window.close(); }, 800);
};
window.onafterprint = function(){ window.close(); };
</script>
</body>
</html>
