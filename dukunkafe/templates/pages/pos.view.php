<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir - <?= htmlspecialchars($settings['cafe_name'] ?? 'Dukun Kafe') ?></title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        [x-cloak] { display: none !important; }
        @media print { .no-print, .no-print * { display: none !important; } }
    </style>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              brand: {
                50: '#f0fdf4', 100: '#dcfce7', 200: '#bbf7d0', 300: '#86efac',
                400: '#4ade80', 500: '#22c55e', 600: '#16a34a', 700: '#15803d',
                800: '#166534', 900: '#14532d'
              }
            }
          }
        }
      }
    </script>
    <script>
      window.ASSET_BASE = "<?= rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/') ?>";
      window.API_READY_ORDERS = "<?= base_url('api/get_ready_orders') ?>";
      window.API_MARK_SERVED = "<?= base_url('api/mark_order_served') ?>";
      window.CASHIER_SHIFT_ACTIVE = <?= isset($cashier_shift_active) && $cashier_shift_active ? 'true' : 'false' ?>;
    </script>
</head>
<body class="h-full bg-gray-100">

    <div class="h-screen flex flex-col" x-data="posApp()" x-init="init()">
        <!-- Toasts -->
        <div class="no-print fixed top-4 right-4 z-[60] space-y-2" x-cloak>
            <template x-for="t in toasts" :key="t.id">
                <div :class="'rounded-md shadow px-4 py-3 text-sm text-white ' + (t.type==='success' ? 'bg-green-600' : t.type==='error' ? 'bg-red-600' : 'bg-gray-800')">
                    <div class="font-semibold" x-text="t.title"></div>
                    <div x-text="t.message"></div>
                </div>
            </template>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex overflow-hidden">

            <!-- Left Panel: Menu & Categories -->
            <div class="flex-1 flex flex-col">
                <!-- Top Bar -->
                <div class="no-print bg-white border-b border-gray-200 p-4 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <a href="<?= base_url('dashboard') ?>" class="text-gray-500 hover:text-gray-800"><i class="fas fa-arrow-left"></i> Dasbor</a>
                        <h1 class="text-xl font-bold text-gray-800">Point of Sale</h1>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="hidden sm:flex items-center">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold"
                                  :class="(cashierShiftActive ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700')">
                                Shift Kasir: <span x-text="cashierShiftActive ? 'Aktif' : 'Tidak Aktif'"></span>
                            </span>
                        </div>
                        <button @click="showReadyModal = true" class="relative text-gray-600 hover:text-gray-800" title="Pesanan Siap">
                            <i class="fas fa-bell text-xl"></i>
                            <span x-show="readyOrders.length > 0" x-cloak
                                  class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] leading-none px-1.5 py-0.5 rounded-full"
                                  x-text="readyOrders.length"></span>
                        </button>
                        <div class="text-right">
                            <div class="font-semibold text-gray-800"><?= htmlspecialchars($_SESSION['user']['name']) ?></div>
                            <div class="text-xs text-gray-500"><?= htmlspecialchars(ucfirst($_SESSION['user']['role'])) ?></div>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-brand-500 text-white flex items-center justify-center font-bold">
                            <?= strtoupper(substr($_SESSION['user']['name'] ?? 'U', 0, 1)) ?>
                        </div>
                    </div>
                </div>

                <!-- Search & Filter -->
                <div class="no-print p-4 bg-white border-b border-gray-200">
                    <div class="relative">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text" x-model.debounce.300ms="searchTerm" placeholder="Cari menu..." class="w-full bg-gray-50 border-gray-200 rounded-full pl-10 pr-4 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div class="mt-3 flex space-x-2 overflow-x-auto pb-2">
                        <button @click="selectedCategory = 'all'" :class="selectedCategory === 'all' ? 'bg-brand-500 text-white' : 'bg-gray-200 text-gray-700'" class="flex-shrink-0 px-4 py-1.5 rounded-full font-semibold text-sm">
                            Semua
                        </button>
                        <?php foreach ($categories as $category): ?>
                        <button @click="selectedCategory = <?= $category['id'] ?>" :class="selectedCategory === <?= $category['id'] ?> ? 'bg-brand-500 text-white' : 'bg-gray-200 text-gray-700'" class="flex-shrink-0 px-4 py-1.5 rounded-full font-semibold text-sm">
                            <?= htmlspecialchars($category['name']) ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Menu Grid -->
                <div class="flex-1 p-4 overflow-y-auto">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                        <template x-for="menu in filteredMenus" :key="menu.id">
                            <div @click="handleMenuClick(menu)" class="relative bg-white rounded-lg shadow-sm overflow-hidden cursor-pointer hover:shadow-md transition-shadow duration-200">
                                <span x-show="hasMenuAddons(menu)" x-cloak class="absolute top-2 right-2 text-[10px] px-2 py-0.5 rounded-full bg-brand-100 text-brand-700 border border-brand-200">Add-on</span>
                                <div class="h-24 bg-gray-200 flex items-center justify-center text-gray-400">
                                    <template x-if="menu.image_url">
                                        <img :src="(String(menu.image_url).startsWith('/') ? (window.ASSET_BASE + menu.image_url) : menu.image_url)" alt="" class="h-full w-full object-cover">
                                    </template>
                                    <template x-if="!menu.image_url">
                                        <i class="fas fa-image fa-2x"></i>
                                    </template>
                                </div>
                                <div class="p-3">
                                    <p class="font-semibold text-gray-800 text-sm truncate" x-text="menu.name"></p>
                                    <p class="text-brand-600 font-bold text-sm" x-text="formatCurrency(menu.price)"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Cart -->
            <div class="no-print w-full md:w-96 flex-shrink-0 flex flex-col bg-white border-l border-gray-200">
                <div class="flex-1 flex flex-col">
                    <!-- Customer & Table -->
                    <div class="p-4 border-b border-gray-200">
                        <div class="grid grid-cols-2 gap-1 bg-gray-200 p-1 rounded-full">
                            <button @click="tableId = 'dine_in'" :class="tableId !== 'take_away' ? 'bg-white shadow-sm' : ''" class="py-1.5 rounded-full font-medium text-sm text-gray-700">Dine In</button>
                            <button @click="tableId = 'take_away'" :class="tableId === 'take_away' ? 'bg-white shadow-sm' : ''" class="py-1.5 rounded-full font-medium text-sm text-gray-700">Take Away</button>
                        </div>
                        <div class="mt-3" x-show="tableId !== 'take_away'">
                            <label class="text-sm font-medium text-gray-600">Pilih Meja</label>
                            <div class="mt-1 grid grid-cols-4 gap-2">
                                <template x-for="table in tables" :key="table.id">
                                    <button @click="table.status === 'AVAILABLE' ? tableId = table.id : null" :disabled="table.status !== 'AVAILABLE'" :class="['p-2 rounded-md border text-xs', tableId === table.id ? 'bg-brand-500 text-white border-brand-500' : 'bg-white border-gray-300', table.status !== 'AVAILABLE' ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-50'].join(' ')" x-text="table.name"></button>
                                </template>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="text-sm font-medium text-gray-600">Nama Pelanggan</label>
                            <input type="text" x-model="customerName" placeholder="(Opsional)" class="mt-1 w-full bg-gray-50 border-gray-200 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                        </div>
                    </div>

                    <!-- Cart Items -->
                    <div class="flex-1 p-4 overflow-y-auto">
                        <template x-if="cart.length === 0">
                            <div class="h-full flex flex-col items-center justify-center text-gray-400">
                                <i class="fas fa-shopping-cart fa-3x"></i>
                                <p class="mt-4">Keranjang kosong</p>
                            </div>
                        </template>
                        <div class="space-y-3">
                            <template x-for="(item, idx) in cart" :key="item.id + '-' + idx">
                                <div class="flex items-start gap-3">
                                    <div class="flex-1">
                                        <p class="text-sm font-semibold text-gray-800" x-text="item.name"></p>
                                        <p class="text-xs text-gray-500" x-text="formatCurrency(item.price)"></p>
                                        <div class="text-xs text-gray-500" x-show="item.addons && item.addons.length > 0">
                                            <template x-for="ad in item.addons" :key="ad.id">
                                                <span x-text="'+ ' + ad.name"></span>
                                            </template>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button @click="updateQtyByIndex(idx, -1)" class="w-6 h-6 bg-gray-200 rounded-full">-</button>
                                        <span class="w-6 text-center text-sm font-medium" x-text="item.qty"></span>
                                        <button @click="updateQtyByIndex(idx, 1)" class="w-6 h-6 bg-gray-200 rounded-full">+</button>
                                    </div>
                                    <p class="text-sm font-semibold text-gray-800" x-text="formatCurrency(calculateItemTotal(item))"></p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Cart Summary -->
                    <div class="p-4 border-t border-gray-200 bg-gray-50">
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm text-gray-600"><span>Subtotal</span><span x-text="formatCurrency(subtotal)"></span></div>
                            <div class="flex justify-between text-sm text-gray-600"><span>Pajak (<span x-text="taxRate"></span>%)</span><span x-text="formatCurrency(taxAmount)"></span></div>
                            <div class="flex justify-between text-sm text-gray-600"><span>Layanan (<span x-text="serviceRate"></span>%)</span><span x-text="formatCurrency(serviceAmount)"></span></div>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <div class="flex justify-between text-lg font-bold text-gray-800"><span>Total</span><span x-text="formatCurrency(total)"></span></div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 mt-4">
                            <button @click="clearCart()" class="w-full p-3 rounded-lg bg-gray-200 text-gray-700 font-bold text-sm">Batal</button>
                            <button @click="openPayment()" :disabled="cart.length === 0" class="w-full p-3 rounded-lg bg-brand-500 text-white font-bold text-sm disabled:opacity-50">Bayar</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- All Modals -->
        <!-- Addon Modal -->
        <div x-show="showAddonModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center no-print">
            <div class="absolute inset-0 bg-black/50" @click="cancelAddonSelection()"></div>
            <div class="relative bg-white rounded-lg shadow-xl w-full max-w-md m-4">
                <div class="p-4 border-b">
                    <h3 class="text-lg font-bold text-gray-800" x-text="'Tambah: ' + selectedMenu?.name"></h3>
                </div>
                <div class="p-4 max-h-[60vh] overflow-y-auto space-y-4">
                    <template x-for="groupId in ((menu_addons[selectedMenu?.id] || selectedMenu?.addon_group_ids || []))" :key="groupId">
                        <div class="border rounded-lg p-3">
                            <p class="font-semibold text-gray-800" x-text="getAddonGroup(groupId)?.name"></p>
                            <p class="text-xs text-gray-500 mb-2" x-text="getAddonGroup(groupId)?.type === 'radio' ? 'Pilih satu' : 'Pilih beberapa'"></p>
                            <div class="space-y-2">
                                <template x-for="addon in (addons_by_group[groupId] || [])" :key="addon.id">
                                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                                        <input :type="getAddonGroup(groupId)?.type" :name="'group-' + groupId" :checked="isAddonSelected(addon)" @change="toggleAddon(addon, getAddonGroup(groupId))" class="form-radio h-4 w-4 text-brand-600" x-show="getAddonGroup(groupId)?.type === 'radio'">
                                        <input type="checkbox" :checked="isAddonSelected(addon)" @change="toggleAddon(addon, getAddonGroup(groupId))" class="form-checkbox h-4 w-4 text-brand-600 rounded" x-show="getAddonGroup(groupId)?.type !== 'radio'">
                                        <span class="flex-1 text-sm text-gray-700" x-text="addon.name"></span>
                                        <span class="text-sm text-gray-500" x-text="'+ ' + formatCurrency(addon.price)"></span>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
                <div class="p-4 bg-gray-50 border-t grid grid-cols-2 gap-3">
                    <button @click="cancelAddonSelection()" class="w-full p-3 rounded-lg bg-gray-200 text-gray-700 font-bold text-sm">Batal</button>
                    <button @click="confirmAddonSelection()" class="w-full p-3 rounded-lg bg-brand-500 text-white font-bold text-sm">Tambahkan</button>
                </div>
            </div>
        </div>

        <!-- Payment Modal -->
        <div x-show="showPaymentModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center no-print">
            <div class="absolute inset-0 bg-black/50" @click="showPaymentModal=false"></div>
            <div class="relative bg-white rounded-lg shadow-xl w-full max-w-sm m-4">
                <div class="p-4 border-b">
                    <h3 class="text-lg font-bold text-gray-800">Pembayaran</h3>
                </div>
                <div class="p-4 space-y-3">
                    <div class="flex justify-between font-medium text-gray-600"><span>Total Tagihan</span><span x-text="formatCurrency(total)"></span></div>
                    <hr/>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Metode Pembayaran</label>
                        <select x-model="paymentMethod" class="w-full bg-gray-50 border-gray-200 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
                            <option value="CASH">Tunai</option>
                            <option value="QRIS">QRIS</option>
                            <option value="CARD">Kartu</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Bayar</label>
                        <input type="number" x-model.number="paidTotal" class="w-full bg-gray-50 border-gray-200 rounded-md px-3 py-2 text-lg font-bold text-right focus:outline-none focus:ring-2 focus:ring-brand-500">
                    </div>
                    <div class="flex justify-between font-medium text-gray-600"><span>Kembalian</span><span x-text="formatCurrency(Math.max(0, (paidTotal||0) - total))"></span></div>
                </div>
                <div class="p-4 bg-gray-50 border-t grid grid-cols-2 gap-3">
                    <button @click="showPaymentModal=false" class="w-full p-3 rounded-lg bg-gray-200 text-gray-700 font-bold text-sm">Batal</button>
                    <button @click="confirmPayment()" :disabled="(paidTotal||0) < total" class="w-full p-3 rounded-lg bg-brand-500 text-white font-bold text-sm disabled:opacity-50">Konfirmasi Bayar</button>
                </div>
            </div>
        </div>

        <!-- Ready Orders Modal -->
        <div x-show="showReadyModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center no-print">
            <div class="absolute inset-0 bg-black/50" @click="showReadyModal = false"></div>
            <div class="relative bg-white rounded-lg shadow-xl w-full max-w-2xl m-4">
                <div class="p-4 border-b flex items-center justify-between">
                    <h3 class="text-lg font-bold text-gray-800">Pesanan Siap Disajikan</h3>
                    <button class="text-gray-500 hover:text-gray-700" @click="showReadyModal = false"><i class="fas fa-times"></i></button>
                </div>
                <div class="p-4 max-h-[70vh] overflow-y-auto">
                    <template x-if="readyOrders.length === 0">
                        <p class="text-sm text-gray-500">Belum ada pesanan siap.</p>
                    </template>
                    <div class="space-y-4" x-show="readyOrders.length > 0">
                        <template x-for="order in readyOrders" :key="order.order_id">
                            <div class="border rounded-lg p-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-semibold text-gray-800" x-text="order.order_no + (order.table_name ? ' • ' + order.table_name : '')"></p>
                                        <p class="text-xs text-gray-500" x-text="order.customer_name"></p>
                                    </div>
                                    <button @click="markOrderServed(order)" class="px-3 py-1.5 bg-brand-600 text-white rounded-md text-sm font-semibold hover:bg-brand-700">Tandai Disajikan</button>
                                </div>
                                <ul class="mt-2 text-sm text-gray-700 list-disc list-inside">
                                    <template x-for="it in order.items" :key="it.id">
                                        <li>
                                            <span x-text="it.name + ' x' + it.qty"></span>
                                            <span class="ml-2 text-xs" :class="it.status === 'READY' ? 'text-green-600' : 'text-gray-500'" x-text="'[' + it.status + ']' "></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hidden form for submission -->
        <form x-ref="orderForm" method="POST" action="<?= base_url('pos') ?>" class="hidden">
            <input type="hidden" name="cart" :value="JSON.stringify(cart)">
            <input type="hidden" name="customer_name" :value="customerName">
            <input type="hidden" name="table_id" :value="tableId">
            <input type="hidden" name="payment_method" :value="paymentMethod">
            <input type="hidden" name="paid_total" :value="paidTotal">
        </form>
    </div>

    <script>
    function posApp() {
        return {
            // Data
            menus: <?= json_encode($menus) ?>,
            categories: <?= json_encode($categories) ?>,
            tables: <?= json_encode($tables_with_status) ?>,
            addon_groups: <?= json_encode($addon_groups) ?>,
            addons_by_group: <?= json_encode($addons_by_group) ?>,
            menu_addons: <?= json_encode($menu_addons) ?>,
            cart: [],
            customerName: '',
            tableId: 'take_away',
            selectedCategory: 'all',
            taxRate: <?= $tax_rate ?>,
            serviceRate: <?= $service_rate ?>,
            searchTerm: '',
            showAddonModal: false,
            selectedMenu: null,
            selectedAddons: [],
            showPaymentModal: false,
            paymentMethod: 'CASH',
            paidTotal: 0,
            // Shift & Ready Orders
            cashierShiftActive: window.CASHIER_SHIFT_ACTIVE === true,
            readyOrders: [],
            showReadyModal: false,
            toasts: [],

            init() {
                <?php if (!empty($_GET['success']) && !empty($_GET['order_id'])) : ?>
                (function(){
                    const oid = '<?= htmlspecialchars($_GET['order_id']) ?>';
                    const w = window.open('<?= base_url("receipt?id=") ?>' + oid, '_blank');
                    if (!w || w.closed) {
                        alert('Gagal membuka tab cetak. Pastikan browser tidak memblokir pop-up.\nAnda bisa cetak ulang dari menu Dasbor -> Transaksi.');
                    }
                })();
                <?php endif; ?>
                // Start polling ready orders
                this.fetchReadyOrders();
                setInterval(() => { this.fetchReadyOrders(); }, 10000);
            },
            
            // Computed Properties
            get filteredMenus() {
                const byCategory = this.selectedCategory === 'all'
                    ? this.menus
                    : this.menus.filter(m => m.category_id == this.selectedCategory);
                const term = (this.searchTerm || '').toLowerCase();
                if (!term) return byCategory;
                return byCategory.filter(m => String(m.name).toLowerCase().includes(term));
            },
            get subtotal() {
                return this.cart.reduce((total, item) => total + this.calculateItemTotal(item), 0);
            },
            get taxAmount() { return this.subtotal * (this.taxRate / 100); },
            get serviceAmount() { return this.subtotal * (this.serviceRate / 100); },
            get total() { return this.subtotal + this.taxAmount + this.serviceAmount; },
            isAddonSelected(addon) { return this.selectedAddons.some(a => a.id === addon.id); },

            // Methods
            handleMenuClick(menu) {
                const hasAddons = this.hasMenuAddons(menu);
                if (hasAddons) {
                    this.selectedMenu = menu;
                    this.selectedAddons = [];
                    this.showAddonModal = true;
                } else {
                    this.addToCart(menu);
                }
            },
            hasMenuAddons(menu) {
                if (!menu) return false;
                if (menu.has_addons === true) return true;
                const key = String(menu.id);
                const groups = (this.menu_addons && (this.menu_addons[key] || this.menu_addons[menu.id])) || menu.addon_group_ids || [];
                return Array.isArray(groups) && groups.length > 0;
            },
            addToCart(menu, addons = []) {
                const existingItem = this.cart.find(item => item.id === menu.id && JSON.stringify(item.addons) === JSON.stringify(addons));
                if (existingItem) {
                    existingItem.qty++;
                } else {
                    this.cart.push({ ...menu, qty: 1, notes: '', addons: addons });
                }
            },
            confirmAddonSelection() {
                this.addToCart(this.selectedMenu, this.selectedAddons);
                this.cancelAddonSelection();
            },
            cancelAddonSelection() {
                this.showAddonModal = false;
                this.selectedMenu = null;
                this.selectedAddons = [];
            },
            toggleAddon(addon, group) {
                if (group.type === 'radio') {
                    this.selectedAddons = this.selectedAddons.filter(a => a.addon_group_id !== group.id);
                    this.selectedAddons.push(addon);
                } else {
                    const index = this.selectedAddons.findIndex(a => a.id === addon.id);
                    if (index > -1) this.selectedAddons.splice(index, 1);
                    else this.selectedAddons.push(addon);
                }
            },
            getAddonGroup(id) { return this.addon_groups.find(g => g.id == id); },
            calculateItemTotal(item) {
                const basePrice = item.price * item.qty;
                const addonPrice = item.addons.reduce((total, addon) => total + (addon.price * item.qty), 0);
                return basePrice + addonPrice;
            },
            updateQtyByIndex(index, amount) {
                const item = this.cart[index];
                if (!item) return;
                item.qty += amount;
                if (item.qty <= 0) this.cart.splice(index, 1);
            },
            clearCart() {
                if (confirm('Yakin ingin membersihkan keranjang?')) this.cart = [];
            },
            formatCurrency(amount) {
                return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(amount);
            },
            async fetchReadyOrders() {
                try {
                    const before = new Set(this.readyOrders.map(o => o.order_id));
                    const res = await fetch(window.API_READY_ORDERS, { credentials: 'same-origin' });
                    if (!res.ok) return;
                    const data = await res.json();
                    const list = Array.isArray(data) ? data : [];
                    // Detect newly ready orders
                    const added = list.filter(o => !before.has(o.order_id));
                    this.readyOrders = list;
                    if (added.length > 0) {
                        const msg = added.length === 1 ? `Pesanan ${added[0].order_no} siap disajikan.` : `${added.length} pesanan siap disajikan.`;
                        this.pushToast({ title: 'Pesanan Siap', message: msg, type: 'success' });
                    }
                } catch (e) { /* ignore */ }
            },
            pushToast({ title = 'Info', message = '', type = 'info' } = {}) {
                const id = Date.now() + Math.random();
                this.toasts.push({ id, title, message, type });
                setTimeout(() => {
                    const idx = this.toasts.findIndex(t => t.id === id);
                    if (idx > -1) this.toasts.splice(idx, 1);
                }, 4000);
            },
            async markOrderServed(order) {
                try {
                    const res = await fetch(window.API_MARK_SERVED, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        credentials: 'same-origin',
                        body: JSON.stringify({ order_id: order.order_id })
                    });
                    const out = await res.json();
                    if (out && out.success) {
                        this.fetchReadyOrders();
                    } else {
                        alert(out.error || 'Gagal memperbarui status pesanan.');
                    }
                } catch (e) {
                    alert('Gagal terhubung ke server.');
                }
            },
            openPayment() {
                if (this.cart.length === 0) return;
                this.paidTotal = this.total;
                this.showPaymentModal = true;
            },
            confirmPayment() {
                if ((this.paidTotal||0) < this.total) {
                    alert('Jumlah bayar kurang dari total tagihan.');
                    return;
                }
                this.$refs.orderForm.submit();
            }
        }
    }
    </script>
</body>
</html>
