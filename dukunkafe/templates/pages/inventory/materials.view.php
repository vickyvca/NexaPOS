
<div x-data="{
    showModal: false,
    isEdit: false,
    material: {},
    openModal(isEdit = false, material = null) {
        this.isEdit = isEdit;
        if (isEdit && material) {
            this.material = { ...material };
        } else {
            this.material = { id: null, code: '', name: '', uom: 'gram', min_stock: 0, active: 1 };
        }
        this.showModal = true;
    },
    closeModal() {
        this.showModal = false;
    }
}">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
        <div class="flex items-center gap-2 bg-brand-50 border border-brand-100 rounded-full px-3 py-1.5 w-full md:w-80">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-brand-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.9 14.32a8 8 0 111.414-1.414l3.387 3.386a1 1 0 01-1.414 1.415l-3.387-3.387zM14 8a6 6 0 11-12 0 6 6 0 0112 0z" clip-rule="evenodd"/></svg>
            <input x-ref="search" oninput="const v=this.value.toLowerCase(); this.closest('[x-data]').querySelectorAll('tbody tr').forEach(tr=>{tr.style.display = (!v || tr.textContent.toLowerCase().includes(v))?'':'none';});" type="text" placeholder="Cari bahan..." class="bg-transparent outline-none text-sm flex-1">
        </div>
        <button @click="openModal(false)" class="px-4 py-2 bg-brand-600 text-white rounded-full shadow-sm hover:bg-brand-700">Tambah Bahan</button>
    </div>

    <!-- Table -->
    <div class="flex flex-col">
        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                <div class="shadow-card overflow-hidden border border-brand-100 sm:rounded-xxl">
                    <table class="min-w-full divide-y divide-brand-100">
                        <thead class="bg-brand-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Code</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">UoM</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Min. Stock</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                <th scope="col" class="relative px-6 py-3">
                                    <span class="sr-only">Edit</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-brand-100" id="tbody-materials">
                            <?php $i=0; foreach ($materials as $mat): $i++; ?>
                                <tr data-role="row" data-index="<?= $i ?>">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-brand-900"><?= htmlspecialchars($mat['name']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-brand-700 font-mono"><?= htmlspecialchars($mat['code']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-brand-700"><?= htmlspecialchars($mat['uom']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-brand-700"><?= htmlspecialchars($mat['min_stock']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $mat['active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                            <?= $mat['active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="openModal(true, <?= htmlspecialchars(json_encode($mat)) ?>)" class="inline-flex items-center px-3 py-1.5 rounded-full border border-brand-200 text-brand-700 hover:bg-brand-50">Edit</button>
                                </td>
                                </tr>
                            <?php endforeach; if (empty($materials)): ?>
                                <tr><td colspan="6" class="px-6 py-10 text-center text-brand-600">Belum ada data bahan.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-between mt-3" id="pager-materials">
        <div class="text-sm text-brand-700"><span id="info-materials">0–0</span></div>
        <div class="space-x-2">
            <button id="prev-materials" class="px-3 py-1.5 rounded-full border border-brand-200 text-brand-700">Prev</button>
            <button id="next-materials" class="px-3 py-1.5 rounded-full border border-brand-200 text-brand-700">Next</button>
        </div>
    </div>

    <script>
    (function(){
      const pageSize = 25;
      const tbody = document.getElementById('tbody-materials');
      if (!tbody) return;
      const rows = Array.from(tbody.querySelectorAll('tr[data-role="row"]'));
      let page = 1; let filtered = rows;
      const search = document.querySelector('[x-ref="search"]');
      const prev = document.getElementById('prev-materials');
      const next = document.getElementById('next-materials');
      const info = document.getElementById('info-materials');
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

    <!-- Modal -->
    <div x-show="showModal" class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div x-show="showModal" x-transition class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form :action="base_url('inventory/materials')" method="POST">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" x-text="isEdit ? 'Edit Material' : 'Add New Material'"></h3>
                        <div class="mt-4 space-y-4">
                            <input type="hidden" name="action" :value="isEdit ? 'update' : 'create'">
                            <input type="hidden" name="id" x-model="material.id">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Material Name</label>
                                <input type="text" name="name" x-model="material.name" required class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Material Code</label>
                                <input type="text" name="code" x-model="material.code" required class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Unit of Measure (UoM)</label>
                                <input type="text" name="uom" x-model="material.uom" required placeholder="e.g., gram, ml, pcs" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Minimum Stock</label>
                                <input type="number" step="0.01" name="min_stock" x-model="material.min_stock" required class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" name="active" x-model="material.active" value="1" class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                <label class="ml-2 block text-sm text-gray-900 dark:text-gray-300">Active</label>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm" x-text="isEdit ? 'Save Changes' : 'Create Material'"></button>
                        <button type="button" @click="closeModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
