
<!DOCTYPE html>
<html lang="en" class="h-full bg-brand-50 text-brand-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Queue</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
      tailwind.config = {
        theme: {
          extend: {
            colors: {
              brand: {
                50: '#eef7f0',
                100: '#d9efe0',
                200: '#b6dfc1',
                300: '#86c897',
                400: '#58b072',
                500: '#2f9452',
                600: '#237543',
                700: '#1c5b37',
                800: '#16472d',
                900: '#123a26'
              }
            },
            boxShadow: {
              card: '0 6px 20px rgba(0,0,0,0.06)'
            },
            borderRadius: {
              xxl: '1.5rem'
            }
          }
        }
      }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="h-full overflow-hidden" x-data="queueDisplay()">

    <div class="flex flex-col h-full">
        <!-- Header -->
        <div class="flex-shrink-0 bg-brand-600 text-white p-4 text-center">
            <?php if (!empty($settings['cafe_logo'])): ?>
                <img src="<?= htmlspecialchars(asset_url($settings['cafe_logo'])) ?>" alt="Logo" class="h-14 mx-auto mb-2 object-contain" />
            <?php endif; ?>
            <h1 class="text-3xl md:text-4xl font-extrabold tracking-tight"><?= htmlspecialchars($settings['cafe_name'] ?? 'DUKUN CAFE') ?></h1>
            <p class="text-base md:text-lg text-white/80">Status Pesanan</p>
        </div>

        <!-- Main Content: Columns -->
        <div class="flex-grow flex h-full">
            <!-- Preparing Column -->
            <div class="w-1/3 flex flex-col p-4 border-r border-brand-100 bg-brand-50">
                <h2 class="flex-shrink-0 text-xl md:text-2xl font-extrabold text-center text-brand-800 mb-4">SEDANG DISIAPKAN</h2>
                <div class="flex-grow grid grid-cols-1 md:grid-cols-2 gap-4 overflow-y-auto">
                    <template x-for="order in preparing" :key="order.order_no">
                        <div class="bg-white rounded-xxl p-4 shadow-card border border-brand-100">
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-2xl font-extrabold text-brand-900" x-text="orderShort(order.order_no)"></div>
                                <div class="text-sm text-brand-700" x-text="order.table_name || 'Take Away'"></div>
                            </div>
                            <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                                <template x-for="it in order.items" :key="it.id">
                                    <div class="flex items-center justify-between bg-brand-50 rounded-lg px-3 py-2 border border-brand-100">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <span class="font-semibold truncate" x-text="it.name"></span>
                                            <span class="text-xs text-brand-600" x-text="'x' + it.qty"></span>
                                        </div>
                                        <span class="text-xs px-2 py-1 rounded-full" :class="statusClass(it.status)" x-text="statusText(it.status)"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Ready Column -->
            <div class="w-1/3 flex flex-col p-4 border-r border-brand-100 bg-brand-50">
                <h2 class="flex-shrink-0 text-xl md:text-2xl font-extrabold text-center text-brand-800 mb-4">SIAP DIAMBIL</h2>
                <div class="flex-grow grid grid-cols-1 md:grid-cols-2 gap-4 overflow-y-auto">
                    <template x-for="order in ready" :key="order.order_no">
                        <div class="bg-white rounded-xxl p-4 shadow-card border border-emerald-200">
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-2xl font-extrabold text-brand-900" x-text="orderShort(order.order_no)"></div>
                                <div class="text-sm text-brand-700" x-text="order.table_name || 'Take Away'"></div>
                            </div>
                            <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                                <template x-for="it in order.items" :key="it.id">
                                    <div class="flex items-center justify-between bg-emerald-50 rounded-lg px-3 py-2 border border-emerald-200">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <span class="font-semibold truncate" x-text="it.name"></span>
                                            <span class="text-xs text-brand-700" x-text="'x' + it.qty"></span>
                                        </div>
                                        <span class="text-xs px-2 py-1 rounded-full bg-emerald-600 text-white">READY</span>
                                    </div>
                                </template>
                            </div>
                            <div class="pt-3 text-right">
                                <button @click="markOrderServed(order)" class="px-3 py-1.5 bg-brand-600 hover:bg-brand-700 text-white rounded-md text-sm">Tandai Diambil</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            <!-- Served Column -->
            <div class="w-1/3 flex flex-col p-4 bg-brand-50">
                <h2 class="flex-shrink-0 text-xl md:text-2xl font-extrabold text-center text-brand-800 mb-4">SUDAH DIAMBIL</h2>
                <div class="flex-grow grid grid-cols-1 md:grid-cols-2 gap-4 overflow-y-auto">
                    <template x-for="order in served" :key="order.order_no">
                        <div class="bg-white rounded-xxl p-4 shadow-card border border-brand-100">
                            <div class="flex items-center justify-between mb-2">
                                <div class="text-2xl font-extrabold text-brand-900" x-text="orderShort(order.order_no)"></div>
                                <div class="text-sm text-brand-700" x-text="order.table_name || 'Take Away'"></div>
                            </div>
                            <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                                <template x-for="it in order.items" :key="it.id">
                                    <div class="flex items-center justify-between bg-brand-50 rounded-lg px-3 py-2 border border-brand-100">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <span class="font-semibold truncate" x-text="it.name"></span>
                                            <span class="text-xs text-brand-600" x-text="'x' + it.qty"></span>
                                        </div>
                                        <span class="text-xs px-2 py-1 rounded-full bg-blue-600 text-white">SERVED</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

<script>
function queueDisplay() {
    return {
        preparing: [],
        ready: [],
        served: [],

        init() {
            this.fetchStatus();
            setInterval(() => this.fetchStatus(), 5000); // Poll every 5 seconds
        },

        fetchStatus() {
            fetch('<?= base_url("api/get_queue_status") ?>')
                .then(res => res.json())
                .then(data => {
                    this.preparing = data.preparing || [];
                    this.ready = data.ready || [];
                    this.served = (data.served || []).slice(-8);
                });
        },

        statusClass(st) {
            if (st === 'READY') return 'bg-emerald-600 text-white';
            if (st === 'IN_PROGRESS') return 'bg-amber-500 text-black';
            return 'bg-brand-600 text-white';
        },
        statusText(st) {
            if (st === 'READY') return 'READY';
            if (st === 'IN_PROGRESS') return 'PROSES';
            return 'ANTRI';
        },
        orderShort(orderNo) {
            try {
                const parts = orderNo.split('-');
                return parts[parts.length - 1] || orderNo;
            } catch (e) { return orderNo; }
        },
        markOrderServed(order) {
            fetch('<?= base_url("api/mark_order_served") ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_no: order.order_no })
            })
            .then(r => r.json())
            .then(() => this.fetchStatus());
        }
    }
}
</script>
</body>
</html>
