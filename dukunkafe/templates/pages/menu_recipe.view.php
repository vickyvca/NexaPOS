<div class="grid grid-cols-1 md:grid-cols-3 gap-6">

    <!-- Recipe Ingredients List -->
    <div class="md:col-span-2">
        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Bahan Resep</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">Daftar bahan yang dibutuhkan untuk membuat <?= htmlspecialchars($menu['name']) ?>.</p>
            </div>
            <div class="border-t border-gray-200 dark:border-gray-700">
                <?php if (empty($recipe_items)): ?>
                    <p class="p-4 text-center text-gray-500 dark:text-gray-400">Belum ada bahan dalam resep ini.</p>
                <?php else: ?>
                    <table class="min-w-full divide-y divide-brand-100">
                        <thead class="bg-brand-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nama Bahan</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Jumlah</th>
                                <th scope="col" class="relative px-6 py-3">
                                    <span class="sr-only">Hapus</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-brand-100">
                            <?php foreach ($recipe_items as $item): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($item['name']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars($item['qty']) ?> <?= htmlspecialchars($item['uom']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="<?= base_url('menu_recipe?id=' . $menu['id'] . '&action=delete&bom_id=' . $item['id']) ?>" 
                                           onclick="return confirm('Yakin ingin menghapus bahan ini dari resep?')" 
                                           class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-200">Hapus</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Ingredient Form -->
    <div>
        <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
            <form action="<?= base_url('menu_recipe?id=' . $menu['id']) ?>" method="POST">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Tambah Bahan</h3>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label for="material_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bahan</label>
                            <select id="material_id" name="material_id" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="">Pilih bahan...</option>
                                <?php foreach ($materials as $material): ?>
                                    <option value="<?= $material['id'] ?>"><?= htmlspecialchars($material['name']) ?> (<?= htmlspecialchars($material['uom']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="qty" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Jumlah</label>
                            <input type="number" name="qty" id="qty" step="0.01" required class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 rounded-md">
                        </div>
                    </div>
                </div>
                <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700 text-right sm:px-6">
                    <button type="submit" name="add_ingredient" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Tambah
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
