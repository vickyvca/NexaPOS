
<div class="bg-white shadow-card border border-brand-100 overflow-hidden sm:rounded-xxl">
    <table class="min-w-full divide-y divide-brand-100">
        <thead class="bg-brand-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold text-brand-700 uppercase">Nomor PO</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-brand-700 uppercase">Pemasok</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-brand-700 uppercase">Tanggal Pesan</th>
                <th class="px-6 py-3 text-right text-xs font-semibold text-brand-700 uppercase">Total</th>
                <th class="relative px-6 py-3"><span class="sr-only">Receive</span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-brand-100 bg-white">
            <?php if (empty($ordered_pos)): ?>
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-sm text-brand-600">Tidak ada PO yang menunggu penerimaan.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($ordered_pos as $po): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-brand-900"><?= htmlspecialchars($po['po_no']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-brand-700"><?= htmlspecialchars($po['supplier_name']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-brand-700"><?= date('d M Y', strtotime($po['created_at'])) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-brand-900 text-right">Rp <?= number_format($po['total'], 0, ',', '.') ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <form method="POST" action="<?= base_url('purchasing/receive') ?>" onsubmit="return confirm('Terima PO ini? Item akan masuk stok.')">
                            <input type="hidden" name="purchase_id" value="<?= $po['id'] ?>">
                            <button type="submit" class="px-3 py-1.5 rounded-full border border-brand-200 text-brand-700 hover:bg-brand-50">Terima</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
