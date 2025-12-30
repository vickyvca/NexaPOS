<?php
// View for the installer page.
// This file is included by layout.php
?>

<?php if (!empty($errors)): ?>
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg" role="alert">
        <p class="font-bold">Error</p>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li>- <?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($installed): ?>
    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg" role="alert">
        <p class="font-bold">Instalasi Selesai</p>
        <p>Aplikasi sudah terinstall. Anda bisa login sekarang.</p>
        <div class="mt-4">
            <a href="<?= htmlspecialchars(base_url('login')) ?>" class="bg-brand-600 hover:bg-brand-700 text-white font-bold py-2 px-4 rounded-full">
                Lanjut ke Login
            </a>
        </div>
    </div>
<?php else: ?>
    <form method="POST" action="<?= htmlspecialchars(base_url('install')) ?>" enctype="multipart/form-data">
        <input type="hidden" name="action" value="install">

        <p class="mb-6 text-gray-600">Selamat datang di Dukun Kafé. Silakan isi form di bawah ini untuk melakukan instalasi awal.</p>

        <div class="space-y-8">
            <div>
                <h3 class="text-xl font-bold text-brand-800 border-b-2 border-brand-100 pb-2 mb-4">1. Informasi Dasar Kafe</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="cafe_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Kafe</label>
                        <input type="text" name="cafe_name" id="cafe_name" required class="w-full" placeholder="Contoh: Dukun Kafé">
                    </div>
                    <div>
                        <label for="cafe_address" class="block text-sm font-medium text-gray-700 mb-1">Alamat Kafe</label>
                        <input type="text" name="cafe_address" id="cafe_address" class="w-full" placeholder="Contoh: Jl. Gaib No. 13">
                    </div>
                    <div>
                        <label for="cafe_logo" class="block text-sm font-medium text-gray-700 mb-1">Logo Kafe (Opsional)</label>
                        <input type="file" name="cafe_logo" id="cafe_logo" class="w-full">
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-xl font-bold text-brand-800 border-b-2 border-brand-100 pb-2 mb-4">2. Pengaturan Finansial</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="tax_rate" class="block text-sm font-medium text-gray-700 mb-1">Pajak PB1 (%)</label>
                        <input type="number" name="tax_rate" id="tax_rate" value="10" step="0.1" class="w-full">
                    </div>
                    <div>
                        <label for="service_rate" class="block text-sm font-medium text-gray-700 mb-1">Service Charge (%)</label>
                        <input type="number" name="service_rate" id="service_rate" value="5" step="0.1" class="w-full">
                    </div>
                     <div>
                        <label for="inventory_mode" class="block text-sm font-medium text-gray-700 mb-1">Mode Inventaris</label>
                        <select name="inventory_mode" id="inventory_mode" class="w-full">
                            <option value="advanced" selected>Advanced (Bahan Baku)</option>
                            <option value="simple">Simple (Stok Produk Jadi)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-xl font-bold text-brand-800 border-b-2 border-brand-100 pb-2 mb-4">3. Akun Administrator</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="admin_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Admin</label>
                        <input type="text" name="admin_name" id="admin_name" value="Administrator" required class="w-full">
                    </div>
                    <div>
                        <label for="admin_email" class="block text-sm font-medium text-gray-700 mb-1">Email Admin</label>
                        <input type="email" name="admin_email" id="admin_email" value="admin@example.com" required class="w-full">
                    </div>
                    <div>
                        <label for="admin_password" class="block text-sm font-medium text-gray-700 mb-1">Password Admin</label>
                        <input type="password" name="admin_password" id="admin_password" placeholder="Default: admin123" class="w-full">
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-10 pt-6 border-t border-gray-200 text-right">
            <button type="submit" class="bg-brand-600 hover:bg-brand-700 text-white font-bold py-3 px-8 rounded-full text-lg">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
                Install Sekarang
            </button>
        </div>
    </form>
<?php endif; ?>