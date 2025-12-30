
<div x-data="{
    showModal: false,
    isEdit: false,
    employee: {},
    roles: <?= htmlspecialchars(json_encode($roles)) ?>,
    openModal(isEdit = false, employee = null) {
        this.isEdit = isEdit;
        if (isEdit && employee) {
            this.employee = { ...employee, pin: '' }; // Clear PIN for security
        } else {
            this.employee = { id: null, nik: '', name: '', pin: '', role_hint: 'waiter', active: 1 };
        }
        this.showModal = true;
    },
    closeModal() {
        this.showModal = false;
    }
}">

    <div class="flex justify-end mb-4">
        <button @click="openModal(false)" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Add Employee</button>
    </div>

    <!-- Table -->
    <div class="flex flex-col">
        <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
                <div class="shadow overflow-hidden border-b border-gray-200 dark:border-gray-700 sm:rounded-lg">
                    <div class="flex justify-between items-center mb-3">
                        <div class="flex items-center gap-2 bg-brand-50 border border-brand-100 rounded-full px-3 py-1.5 w-full md:w-80">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-brand-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.9 14.32a8 8 0 111.414-1.414l3.387 3.386a1 1 0 01-1.414 1.415l-3.387-3.387zM14 8a6 6 0 11-12 0 6 6 0 0112 0z" clip-rule="evenodd"/></svg>
                            <input oninput="const v=this.value.toLowerCase(); const tbody=this.closest('div').nextElementSibling.querySelector('tbody'); tbody.querySelectorAll('tr').forEach(tr=>{tr.style.display = (!v || tr.textContent.toLowerCase().includes(v))?'':'none';});" type="text" placeholder="Cari karyawan..." class="bg-transparent outline-none text-sm flex-1">
                        </div>
                    </div>
                    <table class="min-w-full divide-y divide-brand-100">
                        <thead class="bg-brand-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-brand-700 uppercase tracking-wider">Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-brand-700 uppercase tracking-wider">NIK</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-brand-700 uppercase tracking-wider">Role</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-brand-700 uppercase tracking-wider">Status</th>
                                <th scope="col" class="relative px-6 py-3">
                                    <span class="sr-only">Edit</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-brand-100" id="tbody-employees">
                            <?php if (empty($employees ?? [])): ?>
                                <tr><td colspan="10" class="px-6 py-10 text-center text-brand-600">Belum ada data karyawan.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($employees as $emp): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-brand-900"><?= htmlspecialchars($emp['name']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-brand-700"><?= htmlspecialchars($emp['nik']) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-brand-700"><?= htmlspecialchars(ucfirst($emp['role_hint'])) ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $emp['active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                            <?= $emp['active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button @click="openModal(true, <?= htmlspecialchars(json_encode($emp)) ?>)" class="text-brand-700 hover:text-brand-900">Edit</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div x-show="showModal" class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form :action="base_url('hr/employees')" method="POST">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" x-text="isEdit ? 'Edit Employee' : 'Add New Employee'"></h3>
                        <div class="mt-4 space-y-4">
                            <input type="hidden" name="action" :value="isEdit ? 'update' : 'create'">
                            <input type="hidden" name="id" x-model="employee.id">
                            
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                                <input type="text" name="name" id="name" x-model="employee.name" required class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                            <div>
                                <label for="nik" class="block text-sm font-medium text-gray-700 dark:text-gray-300">NIK (Nomor Induk Karyawan)</label>
                                <input type="text" name="nik" id="nik" x-model="employee.nik" required class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                            <div>
                                <label for="pin" class="block text-sm font-medium text-gray-700 dark:text-gray-300">PIN (4-6 digits for Attendance)</label>
                                <input type="password" name="pin" id="pin" x-model="employee.pin" :placeholder="isEdit ? 'Leave blank to keep unchanged' : ''" :required="!isEdit" pattern="[0-9]{4,6}" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                            <div>
                                <label for="role_hint" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role</label>
                                <select name="role_hint" id="role_hint" x-model="employee.role_hint" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <template x-for="role in roles" :key="role">
                                        <option :value="role" x-text="role.charAt(0).toUpperCase() + role.slice(1)"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" name="active" id="active" x-model="employee.active" :value="1" class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                <label for="active" class="ml-2 block text-sm text-gray-900 dark:text-gray-300">Active</label>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm" x-text="isEdit ? 'Save Changes' : 'Create Employee'"></button>
                        <button type="button" @click="closeModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
