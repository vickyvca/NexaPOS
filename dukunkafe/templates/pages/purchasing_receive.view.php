<?php if (isset($success_message)): ?>
<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
    <p class="font-bold">Sukses</p>
    <p><?= $success_message ?></p>
</div>
<?php endif; ?>
<?php if (isset($error_message)): ?>
<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
    <p class="font-bold">Error</p>
    <p><?= $error_message ?></p>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">

    <!-- Restock Form -->
    <div class="md:col-span-1">
        <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
            <form action="<?= base_url('purchasing_receive') ?>" method="POST">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Form Restock</h3>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="material_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bahan</label>
                            <select id="material_id" name="material_id" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="">Pilih bahan...</option>
                                <?php foreach ($materials as $material): ?>
                                    <option value="<?= $material['id'] ?>"><?= htmlspecialchars($material['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="qty" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Jumlah Diterima</label>
                            <input type="number" name="qty" id="qty" step="0.01" required class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-md">
                        </div>
                        <div>
                            <label for="total_cost" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Total Biaya (Opsional)</label>
                            <input type="number" name="total_cost" id="total_cost" step="0.01" placeholder="Untuk menghitung harga satuan" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-md">
                        </div>
                    </div>
                </div>
                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 text-right sm:px-6">
                    <button type="submit" name="restock" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Simpan Stok
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Current Stock List -->
    <div class="md:col-span-2">
        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Stok Bahan Saat Ini</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">Daftar jumlah stok bahan yang tersedia.</p>
            </div>
            <div class="border-t border-gray-200 dark:border-gray-700" style="max-height: 600px; overflow-y: auto;">
                <table class="min-w-full divide-y divide-brand-100">
                    <thead class="bg-brand-50 sticky top-0">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Bahan</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Stok Saat Ini</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-brand-100">
                        <?php foreach ($material_stocks as $item): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($item['name']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars(number_format($item['current_stock'], 2)) ?> <?= htmlspecialchars($item['uom']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
