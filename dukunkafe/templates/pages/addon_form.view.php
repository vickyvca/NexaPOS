<div class="max-w-2xl mx-auto">
    <form action="<?= base_url('addons?action=save') ?>" method="POST">
        <input type="hidden" name="id" value="<?= $addon['id'] ?? '' ?>">
        <div class="bg-white rounded-xxl shadow-card border border-brand-100">
            <div class="px-6 py-6">
                <h3 class="text-xl font-extrabold text-brand-800"><?= $title ?></h3>
                <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    <div class="sm:col-span-6">
                        <label for="addon_group_id" class="block text-sm font-medium text-brand-700">Grup Add-on</label>
                        <select id="addon_group_id" name="addon_group_id" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border border-brand-200 focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm rounded-xl">
                            <?php foreach (($groups ?? []) as $g): ?>
                                <option value="<?= (int)$g['id'] ?>" <?= (($addon['addon_group_id'] ?? 0) == $g['id']) ? 'selected' : '' ?>><?= htmlspecialchars($g['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="sm:col-span-4">
                        <label for="name" class="block text-sm font-medium text-brand-700">Nama Add-on</label>
                        <input type="text" name="name" id="name" value="<?= htmlspecialchars($addon['name'] ?? '') ?>" required class="mt-1 block w-full border border-brand-200 rounded-xl py-2.5 px-3 sm:text-sm">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="price" class="block text-sm font-medium text-brand-700">Harga</label>
                        <input type="number" name="price" id="price" value="<?= htmlspecialchars($addon['price'] ?? '0') ?>" step="0.01" required class="mt-1 block w-full border border-brand-200 rounded-xl py-2.5 px-3 sm:text-sm">
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 bg-brand-50 border-t border-brand-100 text-right">
                <a href="<?= base_url('addons') ?>" class="mr-3 inline-flex items-center px-4 py-2 rounded-full border border-brand-200 text-brand-700 hover:bg-brand-50">Batal</a>
                <button type="submit" class="inline-flex justify-center py-2 px-5 shadow-sm text-sm font-semibold rounded-full text-white bg-brand-600 hover:bg-brand-700">Simpan</button>
            </div>
        </div>
    </form>
</div>

