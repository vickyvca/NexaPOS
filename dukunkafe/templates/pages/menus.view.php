<div class="bg-white shadow-card border border-brand-100 rounded-xxl overflow-hidden">
    <div class="px-4 py-5 sm:px-6 border-b border-brand-100">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <h3 class="text-lg leading-6 font-extrabold text-brand-800">Daftar Menu</h3>
                <p class="mt-1 max-w-2xl text-sm text-brand-600">Kelola semua item menu yang tersedia.</p>
            </div>
            <div class="flex items-center gap-2 w-full md:w-auto">
                <div class="flex items-center gap-2 bg-brand-50 border border-brand-100 rounded-full px-3 py-1.5 flex-1 md:flex-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-brand-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.9 14.32a8 8 0 111.414-1.414l3.387 3.386a1 1 0 01-1.414 1.415l-3.387-3.387zM14 8a6 6 0 11-12 0 6 6 0 0112 0z" clip-rule="evenodd"/></svg>
                    <input id="search-menus" type="text" placeholder="Cari menu..." class="bg-transparent outline-none text-sm flex-1">
                </div>
                <a href="<?= base_url('menus?action=new') ?>" class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-full shadow-sm text-white bg-brand-600 hover:bg-brand-700">Tambah Menu Baru</a>
            </div>
        </div>
    </div>
    <div>
        <table class="min-w-full divide-y divide-brand-100">
            <thead class="bg-brand-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-brand-700 uppercase tracking-wider">Gambar</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-brand-700 uppercase tracking-wider">SKU</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-brand-700 uppercase tracking-wider">Nama</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-brand-700 uppercase tracking-wider">Kategori</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-brand-700 uppercase tracking-wider">Harga</th>
                    <?php if ($inventory_mode === 'simple'): ?>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-brand-700 uppercase tracking-wider">HPP Manual</th>
                    <?php endif; ?>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-brand-700 uppercase tracking-wider">Status</th>
                    <th class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-brand-100" id="tbody-menus">
                <?php $i=0; foreach ($menus as $m): $i++; ?>
                    <tr data-role="row" data-index="<?= $i ?>">
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <?php if (!empty($m['image_url'])): ?>
                                <img src="<?= htmlspecialchars(asset_url($m['image_url'])) ?>" alt="<?= htmlspecialchars($m['name']) ?>" class="h-10 w-10 object-cover rounded" />
                            <?php else: ?>
                                <div class="h-10 w-10 rounded bg-brand-50 text-brand-400 flex items-center justify-center"><i class="fa-solid fa-image"></i></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-brand-700"><?= htmlspecialchars($m['sku']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-brand-900"><?= htmlspecialchars($m['name']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-brand-700"><?= htmlspecialchars($m['category_name']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-brand-700"><?= number_format($m['price'], 2) ?></td>
                        <?php if ($inventory_mode === 'simple'): ?>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-brand-700"><?= number_format((float)($m['hpp'] ?? 0), 2) ?></td>
                        <?php endif; ?>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $m['is_active'] ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' ?>">
                                <?= $m['is_active'] ? 'Aktif' : 'Non-Aktif' ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                            <?php if ($inventory_mode === 'advanced'): ?>
                                <a href="<?= base_url('menu_recipe?id=' . $m['id']) ?>" class="inline-flex items-center px-3 py-1.5 rounded-full border border-brand-200 text-brand-700 hover:bg-brand-50">Resep</a>
                            <?php endif; ?>
                            <a href="<?= base_url('menus?action=edit&id=' . $m['id']) ?>" class="inline-flex items-center px-3 py-1.5 rounded-full border border-brand-200 text-brand-700 hover:bg-brand-50">Edit</a>
                            <a href="<?= base_url('menus?action=delete&id=' . $m['id']) ?>" onclick="return confirm('Yakin ingin menghapus menu ini?')" class="inline-flex items-center px-3 py-1.5 rounded-full border border-red-200 text-red-600 hover:bg-red-50">Hapus</a>
                        </td>
                    </tr>
                <?php endforeach; if (empty($menus)): ?>
                    <tr><td colspan="8" class="px-6 py-10 text-center text-brand-600">Belum ada data menu.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="flex items-center justify-between px-4 py-3 border-t border-brand-100" id="pager-menus">
        <div class="text-sm text-brand-700"><span id="info-menus">0–0</span></div>
        <div class="space-x-2">
            <button id="prev-menus" class="px-3 py-1.5 rounded-full border border-brand-200 text-brand-700">Prev</button>
            <button id="next-menus" class="px-3 py-1.5 rounded-full border border-brand-200 text-brand-700">Next</button>
        </div>
    </div>
</div>

<script>
(function(){
  const pageSize = 25;
  const tbody = document.getElementById('tbody-menus');
  if (!tbody) return;
  const rows = Array.from(tbody.querySelectorAll('tr[data-role="row"]'));
  let page = 1; let filtered = rows;
  const search = document.getElementById('search-menus');
  const prev = document.getElementById('prev-menus');
  const next = document.getElementById('next-menus');
  const info = document.getElementById('info-menus');
  function render(){
    rows.forEach(tr => tr.style.display = 'none');
    const total = filtered.length;
    const start = (page-1)*pageSize;
    const end = Math.min(start+pageSize, total);
    filtered.slice(start, end).forEach(tr => tr.style.display = '');
    if (info) info.textContent = total ? `${start+1}–${end} dari ${total}` : '0–0';
    prev.disabled = page<=1; next.disabled = end>=total;
  }
  function applyFilter(){
    const q = (search?.value || '').toLowerCase();
    filtered = rows.filter(tr => tr.textContent.toLowerCase().includes(q));
    page = 1; render();
  }
  search?.addEventListener('input', applyFilter);
  prev?.addEventListener('click', ()=>{ if(page>1){ page--; render(); }});
  next?.addEventListener('click', ()=>{ if((page*pageSize)<filtered.length){ page++; render(); }});
  applyFilter();
})();
</script>
