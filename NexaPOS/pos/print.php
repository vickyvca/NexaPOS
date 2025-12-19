<?php
require_once __DIR__ . '/../middleware.php';
$pdo = getPDO();
$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT s.*, u.name kasir FROM sales s LEFT JOIN users u ON u.id=s.created_by WHERE s.id=?");
$stmt->execute([$id]);
$sale = $stmt->fetch();
if (!$sale) die('Not found');
$items = $pdo->prepare("SELECT si.*, i.name FROM sale_items si JOIN items i ON i.id=si.item_id WHERE sale_id=?");
$items->execute([$id]);
$detail = $items->fetchAll();
?>
<!doctype html>
<html>
<head>
    <title>Print Struk</title>
    <style>
        body { font-family: 'Courier New', monospace; }
        .receipt { width: 58mm; margin:0 auto; }
        .text-center { text-align:center; }
        .text-right { text-align:right; }
        .mt { margin-top:8px; }
        hr { border:0; border-top:1px dashed #000; margin:6px 0; }
        table { width:100%; border-collapse:collapse; }
        td { vertical-align:top; font-size:12px; }
        .tot { font-size:13px; font-weight:bold; }
        @media print { @page { size: 58mm auto; margin: 2mm; } }
    </style>
</head>
<body onload="window.print(); setTimeout(() => { window.location.href = '<?= BASE_URL; ?>/pos/index.php'; }, 400);">
<div class="receipt">
    <div class="text-center">
        <div style="font-weight:bold; font-size:14px;"><?= APP_NAME; ?></div>
        <div><?= date('d/m/Y H:i'); ?></div>
    </div>
    <div>No: <?= htmlspecialchars($sale['sale_no']); ?><br>Kasir: <?= htmlspecialchars($sale['kasir']); ?></div>
    <hr>
    <table>
        <?php foreach ($detail as $d): ?>
        <tr>
            <td colspan="2"><?= htmlspecialchars($d['name']); ?></td>
        </tr>
        <tr>
            <td><?= $d['qty']; ?> x <?= number_format($d['price'],0,',','.'); ?></td>
            <td class="text-right"><?= number_format($d['subtotal'],0,',','.'); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <hr>
    <table>
        <tr><td>Total</td><td class="text-right"><?= format_rupiah($sale['total']); ?></td></tr>
        <tr><td>Diskon</td><td class="text-right"><?= format_rupiah($sale['discount']); ?></td></tr>
        <tr><td class="tot">Grand</td><td class="text-right tot"><?= format_rupiah($sale['grand_total']); ?></td></tr>
        <tr><td>Bayar (<?= htmlspecialchars($sale['payment_method']); ?>)</td><td class="text-right"><?= format_rupiah($sale['cash_paid']); ?></td></tr>
        <tr><td>Kembali</td><td class="text-right"><?= format_rupiah($sale['change_amount']); ?></td></tr>
    </table>
    <hr>
    <div class="text-center">Terima kasih</div>
</div>
</body>
</html>
