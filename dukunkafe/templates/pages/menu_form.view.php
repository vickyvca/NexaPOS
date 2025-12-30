<div class="max-w-3xl mx-auto">
    <form action="<?= base_url('menus?action=save') ?>" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= $menu['id'] ?? '' ?>">
        <div class="bg-white rounded-xxl shadow-card border border-brand-100">
            <div class="px-6 py-6">
                <h3 class="text-xl font-extrabold text-brand-800"><?= $title ?></h3>
                <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    
                    <div class="sm:col-span-2">
                        <label for="sku" class="block text-sm font-medium text-brand-700">SKU</label>
                        <input type="text" name="sku" id="sku" value="<?= htmlspecialchars($menu['sku'] ?? '') ?>" required class="mt-1 block w-full border border-brand-200 rounded-xl py-2.5 px-3 sm:text-sm">
                    </div>

                    <div class="sm:col-span-4">
                        <label for="name" class="block text-sm font-medium text-brand-700">Nama Menu</label>
                        <input type="text" name="name" id="name" value="<?= htmlspecialchars($menu['name'] ?? '') ?>" required class="mt-1 block w-full border border-brand-200 rounded-xl py-2.5 px-3 sm:text-sm">
                    </div>

                    <div class="sm:col-span-6">
                        <label for="category_id" class="block text-sm font-medium text-brand-700">Kategori</label>
                        <select id="category_id" name="category_id" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border border-brand-200 focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm rounded-xl">
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= (($menu['category_id'] ?? '') == $category['id']) ? 'selected' : '' ?>><?= htmlspecialchars($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-brand-700">Gambar Menu</label>
                        <div class="mt-1 flex items-center gap-4">
                            <input type="file" name="image_file" accept="image/*" class="block w-full text-sm text-brand-700 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
                        </div>
                        <?php if (!empty($menu['image_url'])): ?>
                            <div class="mt-2">
                                <img src="<?= htmlspecialchars(asset_url($menu['image_url'])) ?>" alt="Preview" class="h-24 rounded-lg border border-brand-100">
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($addon_groups)) : ?>
                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-brand-700 mb-1">Add-on Groups</label>
                        <p class="text-xs text-brand-600 mb-2">Pilih grup add-on yang tersedia untuk menu ini. Grup bertipe <strong>radio</strong> berarti pilih salah satu; <strong>checkbox</strong> berarti bisa pilih beberapa.</p>
                        <div class="grid sm:grid-cols-2 gap-2">
                            <?php foreach ($addon_groups as $ag): $checked = in_array((int)$ag['id'], ($selected_addon_group_ids ?? [])); ?>
                                <label class="flex items-center gap-3 p-3 rounded-xl border <?= $checked ? 'border-brand-400 bg-brand-50' : 'border-brand-200' ?>">
                                    <input type="checkbox" name="addon_group_ids[]" value="<?= (int)$ag['id'] ?>" <?= $checked ? 'checked' : '' ?> class="h-4 w-4 text-brand-600">
                                    <div>
                                        <div class="text-sm font-semibold text-brand-900"><?= htmlspecialchars($ag['name']) ?></div>
                                        <div class="text-xs text-brand-600">
                                            Tipe: <?= htmlspecialchars(strtoupper($ag['type'])) ?><?= !empty($ag['required']) ? ' • Wajib' : '' ?>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="sm:col-span-3">
                        <label for="price" class="block text-sm font-medium text-brand-700">Harga Jual</label>
                        <input type="number" name="price" id="price" value="<?= htmlspecialchars($menu['price'] ?? '0') ?>" step="0.01" required class="mt-1 block w-full border border-brand-200 rounded-xl py-2.5 px-3 sm:text-sm">
                    </div>

                    <?php if ($inventory_mode === 'simple'): ?>
                        <div class="sm:col-span-3">
                            <label for="hpp" class="block text-sm font-medium text-brand-700">HPP (Modal)</label>
                            <input type="number" name="hpp" id="hpp" value="<?= htmlspecialchars($menu['hpp'] ?? '0') ?>" step="0.01" class="mt-1 block w-full border border-brand-200 rounded-xl py-2.5 px-3 sm:text-sm">
                        </div>
                    <?php endif; ?>

                    <div class="sm:col-span-3">
                        <label for="print_station" class="block text-sm font-medium text-brand-700">Stasiun Cetak</label>
                        <select id="print_station" name="print_station" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border border-brand-200 focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm rounded-xl">
                            <option <?= (($menu['print_station'] ?? '') === 'HOT') ? 'selected' : '' ?>>HOT</option>
                            <option <?= (($menu['print_station'] ?? '') === 'GRILL') ? 'selected' : '' ?>>GRILL</option>
                            <option <?= (($menu['print_station'] ?? '') === 'DRINK') ? 'selected' : '' ?>>DRINK</option>
                            <option <?= (($menu['print_station'] ?? '') === 'PASTRY') ? 'selected' : '' ?>>PASTRY</option>
                        </select>
                    </div>

                    <div class="sm:col-span-6">
                        <label class="block text-sm font-medium text-brand-700 mb-1">Status</label>
                        <label class="inline-flex items-center gap-2">
                            <input id="is_active" name="is_active" type="checkbox" value="1" class="h-4 w-4 text-brand-600 focus:ring-brand-500 border-brand-300 rounded" <?= (($menu['is_active'] ?? 1) == 1) ? 'checked' : '' ?>>
                            <span class="text-sm text-brand-900">Menu Aktif</span>
                        </label>
                    </div>

                </div>
            </div>
            <div class="px-6 py-4 bg-brand-50 border-t border-brand-100 text-right">
                <a href="<?= base_url('menus') ?>" class="mr-3 inline-flex items-center px-4 py-2 rounded-full border border-brand-200 text-brand-700 hover:bg-brand-50">Batal</a>
                <button type="submit" class="inline-flex justify-center py-2 px-5 shadow-sm text-sm font-semibold rounded-full text-white bg-brand-600 hover:bg-brand-700">
                    Simpan Menu
                </button>
            </div>
        </div>
    </form>
</div>
