
<div x-data="{
    showPaymentModal: false,
    selectedOrder: null,
    amountPaid: 0,
    paymentMethod: 'CASH',
    openPaymentModal(order) {
        this.selectedOrder = order;
        this.amountPaid = order.total;
        this.showPaymentModal = true;
    },
    get change() {
        return this.amountPaid - this.selectedOrder.total > 0 ? this.amountPaid - this.selectedOrder.total : 0;
    },
    formatCurrency(amount) {
        return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
    },
    setAmount(amount) {
        this.amountPaid = amount;
    }
}">

    <!-- Orders Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <?php if (empty($open_orders)): ?>
            <div class="col-span-full text-center py-12 text-gray-500">
                <p>Tidak ada pesanan yang sedang dibuka.</p>
            </div>
        <?php endif; ?>

        <?php foreach ($open_orders as $order): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 flex flex-col justify-between">
                <div>
                    <div class="flex justify-between items-start">
                        <span class="font-bold text-lg text-gray-800 dark:text-white"><?= htmlspecialchars($order['order_no']) ?></span>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $order['channel'] === 'DINE_IN' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' ?>">
                            <?= htmlspecialchars($order['channel'])
                            ?>
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400"><?= htmlspecialchars($order['table_name'] ?? 'Bawa Pulang') ?></p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Pelanggan: <?= htmlspecialchars($order['customer_name']) ?></p>
                </div>
                <div class="mt-4 pt-4 border-t dark:border-gray-700">
                    <div class="text-right mb-4">
                        <span class="text-xs text-gray-500">TOTAL</span>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">Rp <?= number_format($order['total'], 0, ',', '.') ?></p>
                    </div>
                    <div class="flex gap-2">
                      <button @click='openPaymentModal(<?= json_encode($order) ?>)' class="flex-1 p-3 bg-green-500 text-white rounded-md font-bold hover:bg-green-600">BAYAR</button>
                      <a target="_blank" href="<?= base_url('receipt?id=' . urlencode($order['id'])) ?>" class="px-3 py-3 rounded-md bg-brand-600 text-white font-semibold hover:bg-brand-700" title="Cetak Ulang Nota">
                        <i class="fa-solid fa-print"></i>
                      </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Payment Modal -->
    <div x-show="showPaymentModal" class="fixed z-20 inset-0 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="showPaymentModal = false"></div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-full max-w-sm mx-auto z-30">
                <h3 class="text-lg font-bold mb-4 dark:text-white">Proses Pembayaran</h3>
                <div x-show="selectedOrder">
                    <p class="text-center text-xl mb-2 dark:text-gray-200">Pesanan: <span class="font-mono" x-text="selectedOrder.order_no"></span></p>
                    <form :action="base_url('orders')" method="POST">
                        <input type="hidden" name="order_id" :value="selectedOrder.id">
                        <div class="space-y-4">
                            <div class="p-4 border rounded-lg">
                                <div class="flex justify-between font-bold text-xl dark:text-white">
                                    <span>TOTAL</span>
                                    <span x-text="formatCurrency(selectedOrder.total)"></span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium dark:text-gray-200">Metode Pembayaran</label>
                                <select name="payment_method" x-model="paymentMethod" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                    <option>CASH</option>
                                    <option>CARD</option>
                                    <option>QRIS</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium dark:text-gray-200">Jumlah Dibayar</label>
                                <input type="number" name="paid_total" x-model.number="amountPaid" class="mt-1 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                            </div>
                            <div class="text-lg dark:text-gray-200">
                                <span>Kembalian:</span>
                                <span class="font-bold" x-text="formatCurrency(change)"></span>
                            </div>
                            <div class="flex justify-end space-x-2 pt-4">
                                <button type="button" @click="showPaymentModal = false" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 rounded-md">Batal</button>
                                <button type="submit" :disabled="amountPaid < selectedOrder.total" class="px-4 py-2 bg-green-500 text-white rounded-md disabled:opacity-50">Konfirmasi Pembayaran</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>
