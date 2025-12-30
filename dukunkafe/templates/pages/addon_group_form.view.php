<div class="max-w-2xl mx-auto">
    <form action="<?= base_url('addon_groups?action=save') ?>" method="POST">
        <input type="hidden" name="id" value="<?= $group['id'] ?? '' ?>">
        <div class="bg-white rounded-xxl shadow-card border border-brand-100">
            <div class="px-6 py-6">
                <h3 class="text-xl font-extrabold text-brand-800"><?= $title ?></h3>
                <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
                    <div class="sm:col-span-6">
                        <label for="name" class="block text-sm font-medium text-brand-700">Nama Grup</label>
                        <input type="text" name="name" id="name" value="<?= htmlspecialchars($group['name'] ?? '') ?>" required class="mt-1 block w-full border border-brand-200 rounded-xl py-2.5 px-3 sm:text-sm">
                    </div>
                    <div class="sm:col-span-3">
                        <label for="type" class="block text-sm font-medium text-brand-700">Tipe</label>
                        <select id="type" name="type" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border border-brand-200 focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm rounded-xl">
                            <?php $t = $group['type'] ?? 'checkbox'; ?>
                            <option value="radio" <?= $t === 'radio' ? 'selected' : '' ?>>Radio (pilih satu)</option>
                            <option value="checkbox" <?= $t === 'checkbox' ? 'selected' : '' ?>>Checkbox (boleh beberapa)</option>
                        </select>
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-sm font-medium text-brand-700">Wajib Dipilih</label>
                        <label class="inline-flex items-center gap-2 mt-2">
                            <input id="required" name="required" type="checkbox" value="1" class="h-4 w-4 text-brand-600 focus:ring-brand-500 border-brand-300 rounded" <?= !empty($group['required']) ? 'checked' : '' ?>>
                            <span class="text-sm text-brand-900">Wajib</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 bg-brand-50 border-t border-brand-100 text-right">
                <a href="<?= base_url('addon_groups') ?>" class="mr-3 inline-flex items-center px-4 py-2 rounded-full border border-brand-200 text-brand-700 hover:bg-brand-50">Batal</a>
                <button type="submit" class="inline-flex justify-center py-2 px-5 shadow-sm text-sm font-semibold rounded-full text-white bg-brand-600 hover:bg-brand-700">Simpan</button>
            </div>
        </div>
    </form>
</div>

