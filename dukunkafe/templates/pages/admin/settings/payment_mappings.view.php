
<div class="max-w-2xl mx-auto">
    <h2 class="text-lg font-medium text-brand-800 mb-4">Pemetaan Metode Pembayaran</h2>
    <div class="bg-white p-6 rounded-lg shadow-md">
        <form action="<?= base_url('admin/settings/payment_mappings') ?>" method="POST">
            <div class="space-y-4">
                <?php foreach ($payment_methods as $method): ?>
                <div>
                    <label for="mappings[<?= $method ?>]" class="block mb-2 text-sm font-medium text-gray-900"><?= ucfirst(strtolower($method)) ?></label>
                    <select id="mappings[<?= $method ?>]" name="mappings[<?= $method ?>]" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                        <option value="">-- Pilih Akun --</option>
                        <?php foreach ($accounts as $account): ?>
                        <option value="<?= $account['id'] ?>" <?= ($mappings[$method] ?? '') == $account['id'] ? 'selected' : '' ?>><?= htmlspecialchars($account['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="mt-6">
                <button type="submit" class="text-white bg-brand-600 hover:bg-brand-700 focus:ring-4 focus:outline-none focus:ring-brand-300 font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center">Simpan</button>
            </div>
        </form>
    </div>
</div>
