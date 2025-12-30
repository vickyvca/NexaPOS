
<div x-data="cashbook()" class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="md:col-span-2">
        <h2 class="text-lg font-medium text-brand-800 mb-4">Transaksi Terakhir</h2>
        <div class="bg-white rounded-lg shadow-md">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3">Tanggal</th>
                        <th scope="col" class="px-6 py-3">Akun</th>
                        <th scope="col" class="px-6 py-3">Memo</th>
                        <th scope="col" class="px-6 py-3 text-right">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $transaction): ?>
                    <tr class="bg-white border-b">
                        <td class="px-6 py-4">
                            <?= date('d M Y H:i', strtotime($transaction['created_at'])) ?>
                        </td>
                        <td class="px-6 py-4">
                            <?= htmlspecialchars($transaction['account_name']) ?>
                        </td>
                        <td class="px-6 py-4">
                            <?= htmlspecialchars($transaction['memo']) ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="<?= $transaction['type'] === 'income' ? 'text-green-600' : 'text-red-600' ?>">
                                <?= $transaction['type'] === 'income' ? '+' : '-' ?> Rp <?= number_format($transaction['amount'], 0, ',', '.') ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div>
        <h2 class="text-lg font-medium text-brand-800 mb-4">Saldo Akun</h2>
        <div class="bg-white p-6 rounded-lg shadow-md mb-6">
            <ul>
                <?php foreach ($accounts as $account): ?>
                <li class="flex justify-between items-center py-2 border-b last:border-b-0">
                    <span><?= htmlspecialchars($account['name']) ?></span>
                    <span class="font-semibold">Rp <?= number_format($balances[$account['id']] ?? 0, 0, ',', '.') ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div x-show="!showTransfer">
            <h2 class="text-lg font-medium text-brand-800 mb-4">Tambah Transaksi</h2>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <form action="<?= base_url('accounting/cashbook') ?>" method="POST">
                    <input type="hidden" name="action" value="add_transaction">
                    <div class="mb-4">
                        <label for="account_id" class="block mb-2 text-sm font-medium text-gray-900">Akun</label>
                        <select id="account_id" name="account_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                            <?php foreach ($accounts as $account): ?>
                            <option value="<?= $account['id'] ?>"><?= htmlspecialchars($account['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="type" class="block mb-2 text-sm font-medium text-gray-900">Jenis</label>
                        <select id="type" name="type" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                            <option value="expense">Pengeluaran</option>
                            <option value="income">Pemasukan</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="amount" class="block mb-2 text-sm font-medium text-gray-900">Jumlah</label>
                        <input type="number" id="amount" name="amount" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                    </div>
                    <div class="mb-4">
                        <label for="memo" class="block mb-2 text-sm font-medium text-gray-900">Memo</label>
                        <input type="text" id="memo" name="memo" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                    </div>
                    <button type="submit" class="text-white bg-brand-600 hover:bg-brand-700 focus:ring-4 focus:outline-none focus:ring-brand-300 font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center">Simpan</button>
                    <button type="button" @click="showTransfer = true" class="ml-2 text-sm text-blue-600 hover:underline">Transfer Antar Akun</button>
                </form>
            </div>
        </div>

        <div x-show="showTransfer">
            <h2 class="text-lg font-medium text-brand-800 mb-4">Transfer Antar Akun</h2>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <form action="<?= base_url('accounting/cashbook') ?>" method="POST">
                    <input type="hidden" name="action" value="transfer">
                    <div class="mb-4">
                        <label for="from_account_id" class="block mb-2 text-sm font-medium text-gray-900">Dari Akun</label>
                        <select id="from_account_id" name="from_account_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                            <?php foreach ($accounts as $account): ?>
                            <option value="<?= $account['id'] ?>"><?= htmlspecialchars($account['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="to_account_id" class="block mb-2 text-sm font-medium text-gray-900">Ke Akun</label>
                        <select id="to_account_id" name="to_account_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                            <?php foreach ($accounts as $account): ?>
                            <option value="<?= $account['id'] ?>"><?= htmlspecialchars($account['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="amount" class="block mb-2 text-sm font-medium text-gray-900">Jumlah</label>
                        <input type="number" id="amount" name="amount" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" required>
                    </div>
                    <div class="mb-4">
                        <label for="memo" class="block mb-2 text-sm font-medium text-gray-900">Memo</label>
                        <input type="text" id="memo" name="memo" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" placeholder="Transfer antar akun">
                    </div>
                    <button type="submit" class="text-white bg-brand-600 hover:bg-brand-700 focus:ring-4 focus:outline-none focus:ring-brand-300 font-medium rounded-lg text-sm w-full sm:w-auto px-5 py-2.5 text-center">Transfer</button>
                    <button type="button" @click="showTransfer = false" class="ml-2 text-sm text-red-600 hover:underline">Batal</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function cashbook() {
    return {
        showTransfer: false
    }
}
</script>
