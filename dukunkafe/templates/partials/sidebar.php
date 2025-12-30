<?php
$pdo_sidebar = null;
$settings_sidebar = [];
try {
    $pdo_sidebar = get_pdo();
    $stmt_sb = $pdo_sidebar->query("SHOW TABLES LIKE 'settings'");
    if ($stmt_sb && $stmt_sb->fetchColumn()) {
        $settings_sidebar = load_settings($pdo_sidebar);
    }
} catch (Exception $e) {
    $settings_sidebar = [];
}
$settings_sidebar = array_merge(['inventory_mode' => 'advanced'], $settings_sidebar);
$role = get_user_role();
$show_settings_link = (($settings_sidebar['sidebar_show_settings'] ?? '1') === '1');
$show_branches_link = (($settings_sidebar['sidebar_show_branches'] ?? '1') === '1');
?>

<div class="flex flex-col h-full bg-white border-r border-brand-100">
    <!-- Logo -->
    <div class="flex items-center h-20 flex-shrink-0 px-6">
        <a href="<?= base_url() ?>" class="flex items-center gap-2 text-xl font-extrabold text-brand-800 tracking-tight">
            <?php if (!empty($settings_sidebar['cafe_logo'])): ?>
                <img src="<?= htmlspecialchars(asset_url($settings_sidebar['cafe_logo'])) ?>" alt="Logo" class="w-8 h-8 object-contain rounded" />
            <?php else: ?>
                <i class="fa-solid fa-mug-hot text-brand-600"></i>
            <?php endif; ?>
            <span><?= htmlspecialchars($settings_sidebar['cafe_name'] ?? 'Dukun Kafe') ?></span>
        </a>
    </div>
    <!-- Navigation -->
    <div class="flex-1 flex flex-col overflow-y-auto px-4">
        <nav class="flex-1 space-y-2">
            <?= nav_link('dashboard', 'fa-solid fa-home', 'Dasbor') ?>
            <?= nav_link('pos', 'fa-solid fa-cash-register', 'Kasir') ?>
            <?= nav_link('kitchen', 'fa-solid fa-kitchen-set', 'Tampilan Dapur') ?>
            <?= nav_link('transactions', 'fa-solid fa-receipt', 'Transaksi') ?>
            <?= nav_link('queue-display', 'fa-solid fa-tv', 'Antrian Display') ?>
            <?= nav_link('end_of_day', 'fa-solid fa-calendar-check', 'Rekap & Tutup Shift') ?>
            <?= nav_link('tables', 'fa-solid fa-chair', 'Meja') ?>
            <?php if (in_array($role, ['admin', 'manager'])) : ?>
                <?= nav_link('menus', 'fa-solid fa-book-open', 'Manajemen Menu') ?>
                <div class="pl-6 space-y-2">
                    <?= nav_link('addon_groups', 'fa-solid fa-layer-group', 'Add-on Groups') ?>
                    <?= nav_link('addons', 'fa-solid fa-plus', 'Add-ons') ?>
                </div>
            <?php endif; ?>
            <?php if (($settings_sidebar['inventory_mode'] ?? 'advanced') === 'advanced' && in_array($role, ['admin', 'manager'])) : ?>
                <div class="pt-4">
                    <span class="px-2 text-xs font-semibold uppercase text-gray-400">Inventaris</span>
                    <div class="mt-2 space-y-2">
                        <?= nav_link('inventory/materials', 'fa-solid fa-box', 'Bahan') ?>
                        <?= nav_link('inventory/stock', 'fa-solid fa-boxes-stacked', 'Kartu Stok') ?>
                    </div>
                </div>
                <div class="pt-4">
                    <span class="px-2 text-xs font-semibold uppercase text-gray-400">Pembelian</span>
                    <div class="mt-2 space-y-2">
                        <?= nav_link('purchasing/suppliers', 'fa-solid fa-truck-field', 'Pemasok') ?>
                        <?= nav_link('purchasing/po', 'fa-solid fa-file-invoice', 'Pesanan Pembelian') ?>
                        <?= nav_link('purchasing_receive', 'fa-solid fa-dolly', 'Penerimaan Barang') ?>
                    </div>
                </div>
            <?php endif; ?>
            <?php if (in_array($role, ['admin', 'manager', 'kasir'])) : ?>
            <div class="pt-4">
                <span class="px-2 text-xs font-semibold uppercase text-gray-400">Akuntansi</span>
                <div class="mt-2 space-y-2">
                    <?= nav_link('accounting/cashbook', 'fa-solid fa-book-bookmark', 'Buku Kas') ?>
                    <?= nav_link('admin/settings/payment_mappings', 'fa-solid fa-link', 'Pemetaan Pembayaran') ?>
                </div>
            </div>
            <?php endif; ?>
            <?php if (in_array($role, ['admin', 'hr'])) : ?>
            <div class="pt-4">
                <span class="px-2 text-xs font-semibold uppercase text-gray-400">SDM</span>
                <div class="mt-2 space-y-2">
                    <?= nav_link('hr/employees', 'fa-solid fa-users', 'Karyawan') ?>
                    <?= nav_link('hr/attendance', 'fa-solid fa-user-check', 'Absensi') ?>
                </div>
            </div>
            <?php endif; ?>
        </nav>
    </div>
    <!-- Footer -->
    <?php if (is_logged_in()): ?>
    <div class="flex-shrink-0 flex border-t border-brand-100 p-4">
        <div class="flex-shrink-0 w-full group block">
            <div class="flex items-center">
                <div class="w-9 h-9 rounded-full bg-brand-600 text-white flex items-center justify-center font-semibold">
                  <?= strtoupper(substr($_SESSION['user']['name'] ?? 'U', 0, 1)) ?>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-brand-900"><?= htmlspecialchars($_SESSION['user']['name']) ?></p>
                    <a href="<?= base_url('logout') ?>" class="text-xs font-medium text-brand-700 hover:text-brand-900">Logout</a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php if (in_array($role, ['admin'])) : ?>
    <div class="flex-shrink-0 flex border-t border-brand-100 p-4">
        <?php if ($show_settings_link): ?>
        <a href="<?= base_url('settings') ?>" class="w-full">
            <div class="flex items-center">
                <div class="w-9 h-9 rounded-lg bg-gray-200 text-gray-600 flex items-center justify-center">
                    <i class="fa-solid fa-cog"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-700">Pengaturan</p>
                </div>
            </div>
        </a>
        <?php endif; ?>
    </div>
    <div class="flex-shrink-0 flex border-t border-brand-100 p-4">
        <?php if ($show_branches_link): ?>
        <a href="<?= base_url('branches') ?>" class="w-full">
            <div class="flex items-center">
                <div class="w-9 h-9 rounded-lg bg-gray-200 text-gray-600 flex items-center justify-center">
                    <i class="fa-solid fa-code-branch"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-700">Cabang</p>
                </div>
            </div>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
