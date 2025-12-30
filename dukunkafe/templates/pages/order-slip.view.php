<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Slip <?= htmlspecialchars($order['order_no']) ?></title>
    <style>
        body { font-family: ui-monospace, Menlo, monospace; background:#fff; }
        .wrap { width: 300px; margin: 0 auto; padding:12px; }
        h1 { font-size: 18px; margin:0 0 6px; text-align:center; }
        .meta { text-align:center; font-size: 12px; margin-bottom:8px; }
        .line { border-top:1px dashed #000; margin:8px 0; }
        table { width:100%; font-size: 12px; }
        td { vertical-align: top; padding: 2px 0; }
        .qty { text-align:right; width:30px; }
        .note { font-size:11px; color:#b91c1c; }
        .addon { font-size:11px; color:#1f2937; }
        @media print {
            @page { size: 58mm auto; margin: 2mm; }
            .no-print { display:none; }
            .wrap { width: auto; margin:0; padding:0; }
        }
    </style>
    <script>
        window.addEventListener('load', () => setTimeout(() => window.print(), 200));
        window.addEventListener('afterprint', () => {
            try { window.close(); } catch (e) {}
        });
    </script>
    </head>
<body>
    <div class="wrap">
        <h1>Order #<?= htmlspecialchars($order['order_no']) ?></h1>
        <div class="meta">
            <?= htmlspecialchars($order['customer_name'] ?: '-') ?> • <?= $order['table_name'] ? ('Meja ' . htmlspecialchars($order['table_name'])) : 'Take Away' ?>
        </div>
        <div class="line"></div>
        <table>
            <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><?= htmlspecialchars($it['menu_name']) ?></td>
                        <td class="qty">x<?= htmlspecialchars($it['qty']) ?></td>
                    </tr>
                    <?php if (!empty($it['addons'])): ?>
                        <?php foreach ($it['addons'] as $ad): ?>
                            <tr><td colspan="2" class="addon">+ <?= htmlspecialchars($ad) ?></td></tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php if (!empty($it['notes'])): ?>
                        <tr><td colspan="2" class="note">Note: <?= htmlspecialchars($it['notes']) ?></td></tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="line"></div>
        <div class="no-print" style="text-align:center; margin-top:8px;">
            <button onclick="window.print()">Print</button>
        </div>
    </div>
</body>
</html>
