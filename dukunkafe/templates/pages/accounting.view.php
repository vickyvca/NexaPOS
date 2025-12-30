<?php partial('header'); ?>

<div x-data="accounting()" class="container mx-auto mt-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-extrabold text-brand-800">Buku Kas</h1>
        <div class="space-x-2">
            <button class="bg-brand-300 text-white font-semibold py-2 px-4 rounded-full shadow cursor-not-allowed opacity-60" disabled>Transfer Antar Akun</button>
            <button @click="openModal()" class="bg-brand-600 hover:bg-brand-700 text-white font-semibold py-2 px-4 rounded-full shadow">Tambah Akun Kas</button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="bg-white shadow-card border border-brand-100 rounded-xxl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-brand-100">
                <thead class="bg-brand-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-brand-700 uppercase tracking-wider">Nama Akun</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-brand-700 uppercase tracking-wider">Jenis</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-brand-700 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-brand-100">
                    <?php if (empty($accounts)): ?>
                        <tr>
                            <td colspan="3" class="px-5 py-5 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-center">
                                <p class="text-gray-500 dark:text-gray-400">Tidak ada akun kas yang ditemukan.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($accounts as $account): ?>
                            <tr class="hover:bg-brand-50">
                                <td class="px-5 py-5 bg-transparent text-sm">
                                    <p class="text-brand-900 whitespace-no-wrap"><?= htmlspecialchars($account['name']) ?></p>
                                </td>
                                <td class="px-5 py-5 bg-transparent text-sm">
                                    <p class="text-brand-900 whitespace-no-wrap"><?= htmlspecialchars($account['type']) ?></p>
                                </td>
                                <td class="px-5 py-5 bg-transparent text-sm text-right">
                                    <a href="#" class="font-medium text-blue-600 hover:underline">Ubah</a>
                                    <a href="#" class="ml-4 font-medium text-red-600 hover:underline">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div x-show="isModalOpen" @keydown.escape.window="closeModal()" class="fixed inset-0 bg-black/50 overflow-y-auto h-full w-full z-50" x-cloak>
        <div class="relative top-20 mx-auto p-5 w-full max-w-md shadow-card rounded-xxl bg-white border border-brand-100">
            <div class="mt-3 text-center">
                <h3 class="text-lg leading-6 font-extrabold text-brand-800">Tambah Akun Kas Baru</h3>
                <div class="mt-4 px-7 py-3">
                    <form @submit.prevent="submitForm">
                        <div class="mb-4">
                            <label for="name" class="text-left block text-sm font-medium text-brand-700">Nama Akun</label>
                            <input type="text" x-model="newAccount.name" id="name" class="mt-1 block w-full px-3 py-2 bg-white border border-brand-200 rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm text-brand-900" placeholder="Contoh: Bank Mandiri" required>
                        </div>
                        <div class="mb-4">
                            <label for="type" class="text-left block text-sm font-medium text-brand-700">Jenis Akun</label>
                            <select x-model="newAccount.type" id="type" class="mt-1 block w-full px-3 py-2 bg-white border border-brand-200 rounded-md shadow-sm focus:outline-none focus:ring-brand-500 focus:border-brand-500 sm:text-sm text-brand-900">
                                <option value="cash">Kas Tunai</option>
                                <option value="bank">Bank</option>
                            </select>
                        </div>
                        <div class="items-center px-4 py-3">
                            <button type="submit" class="px-4 py-2 bg-brand-600 text-white text-base font-semibold rounded-full w-full shadow-sm hover:bg-brand-700 focus:outline-none">Simpan</button>
                        </div>
                    </form>
                    <button @click="closeModal()" class="mt-2 px-4 py-2 bg-brand-50 text-brand-800 text-base font-semibold rounded-full w-full shadow-sm hover:bg-brand-100">Batal</button>
                </div>
                <div x-show="message" class="mt-2 text-sm" :class="isError ? 'text-red-600' : 'text-emerald-600'" x-text="message"></div>
            </div>
        </div>
    </div>

    <!-- Development Plan -->
    <div class="mt-8 p-6 bg-gray-100 dark:bg-gray-800/50 rounded-lg">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Rencana Pengembangan Fitur Buku Kas:</h3>
        <ul class="mt-4 list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300">
            <li class="font-bold text-green-600 dark:text-green-400">Formulir untuk menambah/mengedit akun kas (kas, bank, dll). (Selesai)</li>
            <li>Fungsi untuk mencatat transaksi transfer antar akun.</li>
            <li>Tampilan detail (buku besar) untuk setiap akun untuk melihat riwayat transaksi.</li>
            <li>Integrasi pembayaran via QRIS agar otomatis masuk ke akun bank yang sesuai.</li>
        </ul>
        <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">Fitur-fitur ini akan dikembangkan secara bertahap.</p>
    </div>

</div>

<script>
function accounting() {
    return {
        isModalOpen: false,
        newAccount: {
            code: '',
            name: ''
        },
        message: '',
        isError: false,
        openModal() {
            this.isModalOpen = true;
            this.message = '';
            this.isError = false;
        },
        closeModal() {
            this.isModalOpen = false;
        },
        submitForm() {
            fetch('<?= base_url("api/create_account") ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(this.newAccount)
            })
            .then(res => res.json())
            .then(response => {
                if (response.success) {
                    this.isError = false;
                    this.message = response.message || 'Akun berhasil ditambahkan!';
                    setTimeout(() => {
                        this.closeModal();
                        window.location.reload(); // Reload to see the new account
                    }, 1500);
                } else {
                    this.isError = true;
                    this.message = response.message || 'Gagal menambahkan akun.';
                }
            })
            .catch(() => {
                this.isError = true;
                this.message = 'Terjadi kesalahan jaringan. Silakan coba lagi.';
            });
        }
    }
}
</script>

<?php partial('footer'); ?>
