
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="md:col-span-2">
        <h2 class="text-lg font-medium text-brand-800 mb-4">Daftar Akun</h2>
        <div class="bg-white rounded-lg shadow-md">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3">Nama Akun</th>
                        <th scope="col" class="px-6 py-3">Jenis</th>
                        <th scope="col" class="px-6 py-3"><span class="sr-only">Aksi</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($accounts as $account): ?>
                    <tr class="bg-white border-b">
                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                            <?= htmlspecialchars($account['name']) ?>
                        </th>
                        <td class="px-6 py-4">
                            <?= htmlspecialchars($account['type']) ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="#" class="font-medium text-blue-600 hover:underline">Ubah</a>
                            <a href="#" class="ml-4 font-medium text-red-600 hover:underline">Hapus</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div>
        <h2 class="text-lg font-medium text-brand-800 mb-4">Tambah Akun Baru</h2>
        <div class="bg-white p-6 rounded-lg shadow-md">
            <form action="<?= base_url('admin/cash_accounts/create') ?>" method="POST">
                <div class="mb-4">
                    <label for="name" class="block mb-2 text-sm font-medium text-gray-900">Nama Akun</label>
                    <input type="text" id="name" name="name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                </div>
                <div class="mb-4">
                    <label for="type" class="block mb-2 text-sm font-medium text-gray-900">Jenis Akun</label>
                    <select id="type" name="type" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                        <option value="cash">Kas Tunai</option>
                        <option value="bank">Bank</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="balance" class="block mb-2 text-sm font-medium text-gray-900">Saldo Awal</label>
                    <input type="number" id="balance" name="balance" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" value="0">
                </div>
                <button type="submit" class="text-white bg-brand-600 hover:bg-brand-700 focus:ring-4 focus:outline-none focus:ring-brand-300 font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center">Simpan</button>
            </form>
        </div>
    </div>
</div>
