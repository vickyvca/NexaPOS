
<div class="bg-white shadow-card border border-brand-100 rounded-xxl p-4 sm:p-6 mb-6">
    <form method="GET" action="<?= base_url('inventory/stock') ?>">
        <div class="flex">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Cari nama atau kode..." class="w-full px-3 py-3 border border-brand-200 rounded-l-xl text-brand-900">
            <button type="submit" class="-ml-px relative inline-flex items-center px-4 py-3 border border-brand-200 text-sm font-semibold rounded-r-xl text-brand-800 bg-brand-50 hover:bg-brand-100">Saring</button>
        </div>
    </form>
</div>

<!-- Table -->
<div class="flex flex-col">
    <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
            <div class="shadow-card overflow-hidden border border-brand-100 sm:rounded-xxl">
                <table class="min-w-full divide-y divide-brand-100">
                    <thead class="bg-brand-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-brand-700 uppercase tracking-wider">Bahan</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-brand-700 uppercase tracking-wider">Satuan</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-brand-700 uppercase tracking-wider">Stok Minimum</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-brand-700 uppercase tracking-wider">Stok Tersedia</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-brand-700 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-brand-100">
                        <?php foreach ($stock_levels as $item): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-brand-900"><?= htmlspecialchars($item['name']) ?></div>
                                    <div class="text-sm text-brand-700 font-mono"><?= htmlspecialchars($item['code']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-brand-700"><?= htmlspecialchars($item['uom']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-brand-700 text-right"><?= number_format($item['min_stock'], 2) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-brand-900 text-right"><?= number_format($item['qty_on_hand'], 2) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php 
                                        $status = 'OK';
                                        $color = 'bg-green-100 text-green-800';
                                        if ($item['qty_on_hand'] <= 0) {
                                            $status = 'Habis';
                                            $color = 'bg-red-100 text-red-800';
                                        } elseif ($item['qty_on_hand'] <= $item['min_stock']) {
                                            $status = 'Menipis';
                                            $color = 'bg-yellow-100 text-yellow-800';
                                        }
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $color ?>">
                                        <?= $status ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
