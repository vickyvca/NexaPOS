
<!DOCTYPE html>
<html lang="en" class="h-full bg-brand-50 text-brand-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tampilan Dapur</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        .ticket {
            scroll-snap-align: start;
        }
    </style>
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
</head>
<body class="h-full overflow-hidden" x-data="kitchenDisplay()">

    <div class="flex flex-col h-full">
        <!-- Header -->
        <div class="flex-shrink-0 bg-brand-600 text-white p-4 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded bg-white/20 text-white flex items-center justify-center"><i class="fa-solid fa-mug-hot"></i></div>
                <h1 class="text-xl md:text-2xl font-extrabold text-white tracking-tight">Tampilan Dapur</h1>
                <a href="<?= base_url('dashboard') ?>" class="ml-2 px-3 py-1.5 rounded-full bg-white/20 hover:bg-white/30 text-white text-sm">&larr; Dashboard</a>
            </div>
            <div class="text-lg md:text-2xl font-mono text-white" x-text="currentTime"></div>
        </div>

        <!-- Station Filters -->
        <div class="flex-shrink-0 bg-brand-50 p-3 flex gap-2 justify-center border-b border-brand-100">
            <button @click="selectedStation = 'all'" :class="selectedStation === 'all' ? 'bg-brand-600 text-white' : 'bg-white text-white'" class="px-4 py-2 rounded-full text-sm font-semibold shadow-sm border border-brand-100">Semua</button>
            <template x-for="station in stations">
                <button @click="selectedStation = station" :class="selectedStation === station ? 'bg-brand-600 text-white' : 'bg-white text-white'" class="px-4 py-2 rounded-full text-sm font-semibold shadow-sm border border-brand-100">
                    <span x-text="station"></span>
                    <span class="ml-1 text-xs opacity-80" x-text="'(' + stationStats(station).queued + '/' + stationStats(station).inprogress + ')'" title="Antri / Proses"></span>
                </button>
            </template>
        </div>
        <div class="bg-white text-xs text-brand-700 py-1 text-center border-b border-brand-100">Badge: Antri (kuning), Proses (biru)</div>

        <!-- Main Content: Columns -->
        <div class="flex-grow flex space-x-4 p-4 overflow-x-auto">
            <template x-for="station in filteredStations" :key="station">
                <div class="bg-white rounded-xxl w-80 md:w-96 flex-shrink-0 shadow-card border border-brand-100">
                    <h2 class="text-base font-bold p-3 text-center bg-brand-50 rounded-t-xxl text-white border-b border-brand-100" x-text="station"></h2>
                    <div class="p-3 space-y-3 overflow-y-auto h-[calc(100vh-190px)]">
                        <!-- Tickets grouped by order -->
                        <template x-for="ticket in getTicketsForStation(station)" :key="ticket.order_no">
                            <div class="bg-white text-brand-900 rounded-xl shadow-card p-3 space-y-2 ticket border"
                                 :class="overallStatus(ticket) === 'IN_PROGRESS' ? 'border-blue-300' : 'border-yellow-300'">
                                <!-- Ticket Header -->
                                <div class="border-b border-dashed border-brand-200 pb-2 mb-2">
                                    <div class="flex justify-between items-center">
                                        <p class="font-extrabold text-lg">Order #<span x-text="ticket.order_no"></span></p>
                                        <p class="text-sm font-mono" :class="elapsedClass(ticket.created_at)" x-text="timeAgo(ticket.created_at)"></p>
                                    </div>
                                    <p class="text-sm font-semibold text-brand-700">
                                        Meja: <span x-text="ticket.table_name || 'Bawa Pulang'"></span>
                                    </p>
                                </div>

                                <!-- Items within ticket -->
                                <div class="space-y-2">
                                    <template x-for="it in ticket.items" :key="it.id">
                                        <div class="bg-brand-50 rounded-md px-3 py-2 flex items-center justify-between border"
                                             :class="[ it.status === 'IN_PROGRESS' ? 'border-blue-300' : 'border-yellow-300', highlightClass(it.id) ]">
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold truncate max-w-[12rem]" x-text="it.menu_name"></span>
                                                    <span class="text-sm text-brand-600" x-text="'x' + it.qty"></span>
                                                </div>
                                                <div x-show="it.notes" class="text-xs text-red-700 bg-red-100 inline-block px-2 py-0.5 rounded mt-1" x-text="it.notes"></div>
                                                <template x-if="it.addons && it.addons.length">
                                                    <div class="text-[11px] text-brand-700 mt-1 space-y-0.5">
                                                        <template x-for="ad in it.addons" :key="ad.name">
                                                            <div>+ <span x-text="ad.name"></span></div>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <span class="text-[10px] px-2 py-1 rounded-full"
                                                      :class="it.status === 'IN_PROGRESS' ? 'bg-blue-500 text-white' : 'bg-yellow-500 text-black'"
                                                      x-text="it.status === 'IN_PROGRESS' ? 'PROSES' : 'ANTRI'"></span>
                                                <button @click="updateStatus(it.id, 'IN_PROGRESS')" :disabled="it.status !== 'QUEUED'" class="text-blue-600 disabled:text-gray-400" title="Mulai">
                                                    <i class="fa-solid fa-play"></i>
                                                </button>
                                                <button @click="updateStatus(it.id, 'READY')" :disabled="it.status !== 'IN_PROGRESS'" class="text-green-600 disabled:text-gray-400" title="Siap">
                                                    <i class="fa-solid fa-check"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <!-- Batch actions -->
                                <div class="grid grid-cols-2 gap-2 pt-2">
                                    <button @click="updateTicket(ticket, 'IN_PROGRESS')" class="w-full p-2 rounded-md text-white font-bold bg-blue-500 hover:bg-blue-600">
                                        Mulai Semua
                                    </button>
                                    <button @click="updateTicket(ticket, 'READY')" class="w-full p-2 rounded-md text-white font-bold bg-green-500 hover:bg-green-600">
                                        Siap Semua
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

<script>
function kitchenDisplay() {
    return {
        items: [],
        stations: ['HOT', 'GRILL', 'DRINK', 'PASTRY'],
        selectedStation: 'all',
        currentTime: new Date().toLocaleTimeString(),
        seenIds: new Set(),
        highlights: {},

        init() {
            this.fetchOrders();
            setInterval(() => this.fetchOrders(), 5000); // Poll every 5 seconds
            setInterval(() => this.currentTime = new Date().toLocaleTimeString(), 1000);
        },

        fetchOrders() {
            return fetch('<?= base_url("api/get_kitchen_orders") ?>')
                .then(res => res.json())
                .then(data => {
                    const incomingIds = new Set(data.map(d => d.id));
                    const now = Date.now();
                    data.forEach(d => {
                        if (!this.seenIds.has(d.id)) {
                            this.seenIds.add(d.id);
                            this.highlights[d.id] = now + 4000; // highlight for 4s
                        }
                    });
                    // cleanup expired or removed
                    Object.keys(this.highlights).forEach(k => {
                        const id = parseInt(k);
                        if (this.highlights[k] < now || !incomingIds.has(id)) {
                            delete this.highlights[k];
                        }
                    });
                    this.items = data;
                });
        },

        updateStatus(itemId, status) {
            return fetch('<?= base_url("api/update_item_status") ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: itemId, status: status })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    return this.fetchOrders(); // Refresh data after update
                }
            });
        },

        get filteredStations() {
            if (this.selectedStation === 'all') return this.stations;
            return this.stations.filter(s => s === this.selectedStation);
        },

        getItemsForStation(station) {
            return this.items.filter(item => item.print_station === station).sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
        },
        getTicketsForStation(station) {
            const list = this.getItemsForStation(station);
            const map = {};
            list.forEach(it => {
                const key = it.order_no;
                if (!map[key]) {
                    map[key] = { order_no: it.order_no, table_name: it.table_name, created_at: it.created_at, items: [] };
                }
                map[key].items.push(it);
            });
            return Object.values(map).sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
        },
        overallStatus(ticket) {
            return ticket.items.some(it => it.status === 'IN_PROGRESS') ? 'IN_PROGRESS' : 'QUEUED';
        },
        updateTicket(ticket, toStatus) {
            // Apply transition per item respecting allowed transitions
            const promises = [];
            ticket.items.forEach(it => {
                if (toStatus === 'IN_PROGRESS' && it.status === 'QUEUED') {
                    promises.push(this.updateStatus(it.id, 'IN_PROGRESS'));
                }
                if (toStatus === 'READY' && it.status === 'IN_PROGRESS') {
                    promises.push(this.updateStatus(it.id, 'READY'));
                }
            });
            return Promise.all(promises).then(() => this.fetchOrders());
        },
        
        timeAgo(timestamp) {
            const now = new Date();
            const past = new Date(timestamp);
            const seconds = Math.floor((now - past) / 1000);
            let interval = seconds / 60; // minutes
            if (interval > 1) {
                return Math.floor(interval) + " menit yang lalu";
            }
            return Math.floor(seconds) + " detik yang lalu";
        },
        elapsedClass(timestamp) {
            const minutes = Math.max(0, Math.floor((new Date() - new Date(timestamp)) / 60000));
            if (minutes >= 10) return 'text-red-600';
            if (minutes >= 5) return 'text-amber-600';
            return 'text-green-600';
        },
        stationStats(station) {
            const list = this.getItemsForStation(station);
            let queued = 0, inprogress = 0;
            list.forEach(i => { if (i.status === 'QUEUED') queued++; if (i.status === 'IN_PROGRESS') inprogress++; });
            return { queued, inprogress };
        },
        highlightClass(id) {
            return this.highlights[id] ? 'ring-2 ring-brand-400 animate-pulse' : '';
        }
    }
}
</script>
</body>
</html>
