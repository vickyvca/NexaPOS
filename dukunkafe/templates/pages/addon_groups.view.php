<div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-extrabold text-brand-800">Add-on Groups</h2>
        <a href="<?= base_url('addon_groups?action=new') ?>" class="inline-flex items-center px-4 py-2 rounded-full bg-brand-600 text-white text-sm font-semibold hover:bg-brand-700"><i class="fa fa-plus mr-2"></i>Tambah Grup</a>
    </div>
    <div class="bg-white rounded-xxl shadow-card border border-brand-100 overflow-hidden">
        <table class="min-w-full divide-y divide-brand-100">
            <thead class="bg-brand-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-brand-700 uppercase tracking-wider">Nama</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-brand-700 uppercase tracking-wider">Tipe</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-brand-700 uppercase tracking-wider">Wajib</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-brand-100">
                <?php if (empty($groups)): ?>
                    <tr><td colspan="4" class="px-6 py-4 text-sm text-brand-700">Belum ada grup.</td></tr>
                <?php else: foreach ($groups as $g): ?>
                    <tr>
                        <td class="px-6 py-3 text-sm font-medium text-brand-900"><?= htmlspecialchars($g['name']) ?></td>
                        <td class="px-6 py-3 text-sm text-brand-700"><?= htmlspecialchars(strtoupper($g['type'])) ?></td>
                        <td class="px-6 py-3 text-sm text-brand-700"><?= !empty($g['required']) ? 'Ya' : 'Tidak' ?></td>
                        <td class="px-6 py-3 text-right">
                            <a href="<?= base_url('addon_groups?action=edit&id=' . $g['id']) ?>" class="text-brand-700 hover:text-brand-900 text-sm font-semibold mr-3">Edit</a>
                            <a href="<?= base_url('addon_groups?action=delete&id=' . $g['id']) ?>" class="text-red-600 hover:text-red-700 text-sm font-semibold" onclick="return confirm('Hapus grup ini?')">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        <a href="<?= base_url('addons') ?>" class="inline-flex items-center px-3 py-1.5 rounded-full border border-brand-200 text-brand-700 hover:bg-brand-50 text-sm">Kelola Add-ons</a>
    </div>
</div>

