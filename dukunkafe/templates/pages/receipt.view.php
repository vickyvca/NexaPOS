
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt <?= htmlspecialchars($order['order_no']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'monospace', sans-serif;
            background-color: #f5f5f5;
        }
        .receipt-container {
            max-width: 320px; /* Approx 80mm */
            margin: 2rem auto;
            padding: 1rem;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .receipt-header, .receipt-footer {
            text-align: center;
        }
        .receipt-header h1 {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0;
        }
        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        .receipt-table th, .receipt-table td {
            padding: 0.25rem 0;
            font-size: 0.8rem;
        }
        .receipt-table .item-name {
            text-align: left;
        }
        .receipt-table .item-qty, .receipt-table .item-price, .receipt-table .item-total {
            text-align: right;
        }
        .totals-table {
            width: 100%;
            margin-top: 1rem;
        }
        .totals-table td {
            padding: 0.1rem 0;
            font-size: 0.8rem;
        }
        .totals-table .label {
            text-align: left;
        }
        .totals-table .value {
            text-align: right;
        }
        .dotted-line {
            border-top: 1px dashed #333;
            margin: 0.5rem 0;
        }
        .no-print {
            margin-top: 2rem;
            text-align: center;
        }

        @media print {
            body {
                background-color: white;
            }
            .receipt-container {
                max-width: 100%;
                margin: 0;
                box-shadow: none;
                padding: 0;
            }
            .no-print {
                display: none;
            }
            /* Adjust font size for 58mm printers */
            @page {
                size: 80mm auto; /* Adjust width as needed */
                margin: 2mm;
            }
        }
    </style>
    <script>
        window.addEventListener('load', () => setTimeout(() => window.print(), 200));
        window.addEventListener('afterprint', () => {
            try {
                if (window.opener) {
                    window.opener.postMessage({ type: 'receipt-printed', orderId: '<?= (int)$order['id'] ?>' }, '*');
                }
            } catch (e) {}
            window.close();
        });
    </script>
</head>
<body>

    <div class="receipt-container">
        <div class="receipt-header">
            <?php if (!empty($settings['cafe_logo'])): ?>
                <div style="margin-bottom:6px;">
                    <img src="<?= htmlspecialchars(asset_url($settings['cafe_logo'])) ?>" alt="Logo" style="max-height:48px; object-fit:contain; margin:0 auto;" />
                </div>
            <?php endif; ?>
            <h1><?= htmlspecialchars($settings['cafe_name'] ?? 'Dukun Cafe') ?></h1>
            <?php if (!empty($settings['cafe_address'])): ?>
                <p><?= nl2br(htmlspecialchars($settings['cafe_address'])) ?></p>
            <?php endif; ?>
            <p><?= date("d/m/Y H:i:s") ?></p>
        </div>

        <div class="dotted-line"></div>
        
        <div>
            <p>Order #: <?= htmlspecialchars($order['order_no']) ?></p>
            <p>Cashier: <?= htmlspecialchars($order['cashier_name']) ?></p>
            <p>Table: <?= htmlspecialchars($order['table_name'] ?? 'Take Away') ?></p>
        </div>

        <table class="receipt-table">
            <thead>
                <tr>
                    <th class="item-name">Item</th>
                    <th class="item-qty">Qty</th>
                    <th class="item-price">Price</th>
                    <th class="item-total">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td class="item-name" colspan="4"><?= htmlspecialchars($item['menu_name']) ?></td>
                </tr>
                <tr>
                    <td></td>
                    <td class="item-qty"><?= htmlspecialchars($item['qty']) ?>x</td>
                    <td class="item-price"><?= number_format($item['price'], 0, ',', '.') ?></td>
                    <td class="item-total"><?= number_format($item['qty'] * $item['price'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="dotted-line"></div>

        <table class="totals-table">
            <tbody>
                <tr>
                    <td class="label">Subtotal</td>
                    <td class="value">Rp <?= number_format($order['subtotal'], 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <td class="label">Tax</td>
                    <td class="value">Rp <?= number_format($order['tax'], 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <td class="label">Service</td>
                    <td class="value">Rp <?= number_format($order['service'], 0, ',', '.') ?></td>
                </tr>
                <tr style="font-weight: bold;">
                    <td class="label">Total</td>
                    <td class="value">Rp <?= number_format($order['total'], 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <td class="label">Paid (<?= htmlspecialchars($order['payment_method']) ?>)</td>
                    <td class="value">Rp <?= number_format($order['paid_total'], 0, ',', '.') ?></td>
                </tr>
                <tr>
                    <td class="label">Change</td>
                    <td class="value">Rp <?= number_format($order['paid_total'] - $order['total'], 0, ',', '.') ?></td>
                </tr>
            </tbody>
        </table>

        <div class="dotted-line"></div>

        <div class="receipt-footer">
            <p>Terima kasih atas kunjungan Anda!</p>
        </div>
    </div>

    <div class="no-print">
        <button onclick="window.print();" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Print</button>
        <a href="<?= base_url('orders') ?>" class="px-6 py-2 bg-gray-300 text-black rounded-md">Back to Orders</a>
    </div>

</body>
</html>
