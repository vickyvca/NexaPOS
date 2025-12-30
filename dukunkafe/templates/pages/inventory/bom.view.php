
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">

    <!-- Left Column: Menu Selection & BOM Table -->
    <div class="md:col-span-2">
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4 sm:p-6">
            <div class="mb-4">
                <label for="menu_select" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Select Menu to Edit BOM</label>
                <select id="menu_select" onchange="if (this.value) window.location.href='<?= base_url('inventory/bom') ?>?menu_id='+this.value" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">-- Select a Menu --</option>
                    <?php foreach ($menus as $menu): ?>
                        <option value="<?= $menu['id'] ?>" <?= ($selected_menu_id == $menu['id']) ? 'selected' : '' ?>><?= htmlspecialchars($menu['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ($selected_menu_id): ?>
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Recipe for: <?= htmlspecialchars(array_column($menus, 'name', 'id')[$selected_menu_id] ?? '') ?></h3>
            <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                    <div class="shadow overflow-hidden border-b border-gray-200 dark:border-gray-700 sm:rounded-lg">
                        <table class="min-w-full divide-y divide-brand-100">
                            <thead class="bg-brand-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Material</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Quantity</th>
                                    <th class="relative px-6 py-3"><span class="sr-only">Delete</span></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-brand-100">
                                <?php if (empty($bom_items)): ?>
                                    <tr><td colspan="3" class="text-center py-4 text-gray-500">No recipe defined for this menu.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($bom_items as $item): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($item['name']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars($item['qty']) . ' ' . htmlspecialchars($item['uom']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <form action="<?= base_url('inventory/bom') ?>" method="POST" onsubmit="return confirm('Are you sure you want to remove this item?');">
                                            <input type="hidden" name="action" value="delete_material">
                                            <input type="hidden" name="menu_id" value="<?= $selected_menu_id ?>">
                                            <input type="hidden" name="bom_id" value="<?= $item['id'] ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-200">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Column: Add Material Form -->
    <div>
        <?php if ($selected_menu_id): ?>
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Add Material to Recipe</h3>
            <form action="<?= base_url('inventory/bom') ?>" method="POST" class="mt-4 space-y-4">
                <input type="hidden" name="action" value="add_material">
                <input type="hidden" name="menu_id" value="<?= $selected_menu_id ?>">
                
                <div>
                    <label for="material_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Material</label>
                    <select name="material_id" id="material_id" required class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">-- Select Material --</option>
                        <?php foreach ($materials as $material): ?>
                            <option value="<?= $material['id'] ?>"><?= htmlspecialchars($material['name']) ?> (<?= htmlspecialchars($material['uom']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="qty" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Quantity</label>
                    <input type="number" step="0.01" name="qty" id="qty" required class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>

                <div>
                    <label for="uom" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Unit</label>
                    <input type="text" name="uom" id="uom" required placeholder="e.g., gram, ml, pcs" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>

                <div>
                    <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Add to BOM</button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>

</div>
