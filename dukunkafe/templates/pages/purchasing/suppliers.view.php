
<div x-data="{
    showModal: false,
    isEdit: false,
    supplier: {},
    openModal(isEdit = false, supplier = null) {
        this.isEdit = isEdit;
        if (isEdit && supplier) {
            this.supplier = { ...supplier };
        } else {
            this.supplier = { id: null, code: '', name: '', contact: '', phone: '', address: '' };
        }
        this.showModal = true;
    }
}">

    <div class="flex justify-end mb-4">
        <button @click="openModal(false)" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Add Supplier</button>
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
        <div class="flex justify-between items-center mb-3">
            <div class="flex items-center gap-2 bg-brand-50 border border-brand-100 rounded-full px-3 py-1.5 w-full md:w-80">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-brand-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.9 14.32a8 8 0 111.414-1.414l3.387 3.386a1 1 0 01-1.414 1.415l-3.387-3.387zM14 8a6 6 0 11-12 0 6 6 0 0112 0z" clip-rule="evenodd"/></svg>
                <input oninput="const v=this.value.toLowerCase(); const tbody=this.closest('div').nextElementSibling.querySelector('tbody'); tbody.querySelectorAll('tr').forEach(tr=>{tr.style.display = (!v || tr.textContent.toLowerCase().includes(v))?'':'none';});" type="text" placeholder="Cari pemasok..." class="bg-transparent outline-none text-sm flex-1">
            </div>
        </div>
        <table class="min-w-full divide-y divide-brand-100">
            <thead class="bg-brand-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Contact</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Phone</th>
                    <th class="relative px-6 py-3"><span class="sr-only">Edit</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-brand-100 bg-white" id="tbody-suppliers">
                <?php if (empty($suppliers ?? [])): ?>
                    <tr><td colspan="10" class="px-6 py-10 text-center text-brand-600">Belum ada data pemasok.</td></tr>
                <?php endif; ?>
                <?php foreach ($suppliers as $sup): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($sup['name']) ?></div>
                            <div class="text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars($sup['code']) ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars($sup['contact']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars($sup['phone']) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button @click="openModal(true, <?= htmlspecialchars(json_encode($sup)) ?>)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">Edit</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Modal -->
    <div x-show="showModal" class="fixed z-10 inset-0 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showModal = false"></div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-lg mx-auto z-30">
                <h3 class="text-lg font-medium mb-4" x-text="isEdit ? 'Edit Supplier' : 'Add Supplier'"></h3>
                <form method="POST" action="<?= base_url('purchasing/suppliers') ?>" class="space-y-4">
                    <input type="hidden" name="action" :value="isEdit ? 'update' : 'create'">
                    <input type="hidden" name="id" x-model="supplier.id">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium">Supplier Name</label>
                            <input type="text" name="name" x-model="supplier.name" required class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600">
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Supplier Code</label>
                            <input type="text" name="code" x-model="supplier.code" required class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600">
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Contact Person</label>
                            <input type="text" name="contact" x-model="supplier.contact" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600">
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Phone</label>
                            <input type="text" name="phone" x-model="supplier.phone" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium">Address</label>
                            <textarea name="address" x-model="supplier.address" rows="3" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600"></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-2 pt-4">
                        <button type="button" @click="showModal = false" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 rounded-md">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
