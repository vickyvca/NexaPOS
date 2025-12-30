<form action="<?= base_url('settings') ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
    <div>
        <h3 class="text-lg font-medium leading-6 text-gray-900">Informasi Kafe</h3>
        <p class="mt-1 text-sm text-gray-600">Informasi ini akan ditampilkan pada struk pembayaran.</p>
    </div>

    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
        <div class="sm:col-span-4">
            <label for="cafe_name" class="block text-sm font-medium text-gray-700">Nama Kafe</label>
            <input type="text" name="cafe_name" id="cafe_name" value="<?= htmlspecialchars($settings['cafe_name'] ?? '') ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
        </div>

        <div class="sm:col-span-6">
            <label for="cafe_address" class="block text-sm font-medium text-gray-700">Alamat Kafe</label>
            <textarea name="cafe_address" id="cafe_address" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm"><?= htmlspecialchars($settings['cafe_address'] ?? '') ?></textarea>
        </div>

        <div class="sm:col-span-6">
            <label class="block text-sm font-medium text-gray-700">Logo Kafe</label>
            <div class="mt-1 flex items-center">
                <?php if (!empty($settings['cafe_logo'])): ?>
                    <img src="<?= htmlspecialchars(asset_url($settings['cafe_logo'])) ?>" alt="Logo Kafe" class="h-16 w-16 object-contain mr-4">
                <?php endif; ?>
                <input type="file" name="cafe_logo" id="cafe_logo" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100">
            </div>
        </div>
    </div>

    <div>
        <h3 class="text-lg font-medium leading-6 text-gray-900">Pengaturan Keuangan</h3>
    </div>

    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
        <div class="sm:col-span-3">
            <label for="tax_rate" class="block text-sm font-medium text-gray-700">Tarif Pajak (%)</label>
            <input type="number" step="0.01" name="tax_rate" id="tax_rate" value="<?= htmlspecialchars($settings['tax_rate'] ?? '0') ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
        </div>

        <div class="sm:col-span-3">
            <label for="service_rate" class="block text-sm font-medium text-gray-700">Tarif Layanan (%)</label>
            <input type="number" step="0.01" name="service_rate" id="service_rate" value="<?= htmlspecialchars($settings['service_rate'] ?? '0') ?>" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm">
        </div>
    </div>

    <div>
        <h3 class="text-lg font-medium leading-6 text-gray-900">Mode Aplikasi</h3>
    </div>

    <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
        <div class="sm:col-span-3">
            <label for="inventory_mode" class="block text-sm font-medium text-gray-700">Mode Inventaris</label>
            <select name="inventory_mode" id="inventory_mode" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm rounded-md">
                <option value="simple" <?= ($settings['inventory_mode'] ?? 'advanced') === 'simple' ? 'selected' : '' ?>>Sederhana</option>
                <option value="advanced" <?= ($settings['inventory_mode'] ?? 'advanced') === 'advanced' ? 'selected' : '' ?>>Lanjutan</option>
            </select>
        </div>
    </div>

    <div class="pt-5">
        <div class="flex justify-end">
            <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">Simpan Pengaturan</button>
        </div>
    </div>
</form>
