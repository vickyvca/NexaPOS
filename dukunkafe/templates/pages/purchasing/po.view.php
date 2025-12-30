
<div x-data="poForm()">
    <!-- Main Content -->
    <div class="flex justify-end mb-4">
        <button @click="showCreateForm = true" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Create New PO</button>
    </div>

    <!-- PO List Table -->
    <div x-show="!showCreateForm">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Existing Purchase Orders</h3>
        <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
            <div class="flex justify-between items-center mb-3">
                <div class="flex items-center gap-2 bg-brand-50 border border-brand-100 rounded-full px-3 py-1.5 w-full md:w-80">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-brand-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.9 14.32a8 8 0 111.414-1.414l3.387 3.386a1 1 0 01-1.414 1.415l-3.387-3.387zM14 8a6 6 0 11-12 0 6 6 0 0112 0z" clip-rule="evenodd"/></svg>
                    <input oninput="const v=this.value.toLowerCase(); const tbody=this.closest('div').nextElementSibling.querySelector('tbody'); tbody.querySelectorAll('tr').forEach(tr=>{tr.style.display = (!v || tr.textContent.toLowerCase().includes(v))?'':'none';});" type="text" placeholder="Cari PO..." class="bg-transparent outline-none text-sm flex-1">
                </div>
            </div>
            <table class="min-w-full divide-y divide-brand-100" id="table-po">
                <thead class="bg-brand-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">PO Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Supplier</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-100 bg-white" id="tbody-po">
                    <?php if (empty($purchase_orders ?? [])): ?>
                        <tr><td colspan="10" class="px-6 py-10 text-center text-brand-600">Belum ada data PO.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($purchase_orders as $po): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($po['po_no']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= htmlspecialchars($po['supplier_name']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?= date('d M Y', strtotime($po['created_at'])) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-right">Rp <?= number_format($po['total'], 0, ',', '.') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800"><?= htmlspecialchars($po['status']) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create PO Form -->
    <div x-show="showCreateForm" x-cloak>
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Create New Purchase Order</h3>
        <form method="POST" action="<?= base_url('purchasing/po') ?>">
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-lg p-6 space-y-6">
                <!-- Header -->
                <div>
                    <label for="supplier_id" class="block text-sm font-medium">Supplier</label>
                    <select name="supplier_id" required class="mt-1 block w-full md:w-1/3 shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600">
                        <option value="">-- Select Supplier --</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?= $supplier['id'] ?>"><?= htmlspecialchars($supplier['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Items -->
                <div class="space-y-2">
                    <h4 class="font-medium">Items</h4>
                    <template x-for="(item, index) in items" :key="index">
                        <div class="flex items-center space-x-2 p-2 bg-gray-50 dark:bg-gray-700 rounded-md">
                            <select @change="updateItem(index, $event.target.value)" class="flex-grow shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-600">
                                <option value="">-- Select Material --</option>
                                <template x-for="material in materials">
                                    <option :value="material.id" x-text="material.name + ' (' + material.uom + ')'"></option>
                                </template>
                            </select>
                            <input type="number" x-model.number="item.qty" placeholder="Qty" class="w-20 shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-600">
                            <input type="number" x-model.number="item.price" placeholder="Price" class="w-32 shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-600">
                            <div class="w-32 font-semibold" x-text="formatCurrency(item.qty * item.price)"></div>
                            <button type="button" @click="removeItem(index)" class="text-red-500 hover:text-red-700">X</button>
                        </div>
                    </template>
                    <button type="button" @click="addItem()" class="px-3 py-1 bg-gray-200 dark:bg-gray-600 text-sm rounded-md">+ Add Item</button>
                </div>

                <!-- Footer -->
                <div class="pt-4 border-t dark:border-gray-700 flex justify-between items-center">
                    <div>
                        <h4 class="text-lg font-bold">Total: <span x-text="formatCurrency(total)"></span></h4>
                    </div>
                    <div>
                        <button type="button" @click="showCreateForm = false" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 rounded-md">Cancel</button>
                        <button type="submit" name="status" value="DRAFT" class="px-4 py-2 bg-yellow-500 text-white rounded-md">Save as Draft</button>
                        <button type="submit" name="status" value="ORDERED" class="px-4 py-2 bg-green-500 text-white rounded-md">Submit PO</button>
                    </div>
                </div>
            </div>
            <input type="hidden" name="items" :value="JSON.stringify(items.map(i => ({id: i.id, qty: i.qty, price: i.price, uom: i.uom})))">
        </form>
    </div>
</div>

<script>
function poForm() {
    return {
        showCreateForm: false,
        materials: <?= json_encode($materials) ?>,
        items: [],
        init() {
            this.addItem();
        },
        addItem() {
            this.items.push({ id: '', name: '', uom: '', qty: 1, price: 0 });
        },
        removeItem(index) {
            this.items.splice(index, 1);
        },
        updateItem(index, materialId) {
            const material = this.materials.find(m => m.id == materialId);
            if (material) {
                this.items[index].id = material.id;
                this.items[index].name = material.name;
                this.items[index].uom = material.uom;
            }
        },
        get total() {
            return this.items.reduce((sum, item) => sum + (item.qty * item.price), 0);
        },
        formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
        }
    }
}
</script>
