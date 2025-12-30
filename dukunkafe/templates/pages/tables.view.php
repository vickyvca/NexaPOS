<div x-data="tableLayout()" x-init="init()">
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8 gap-5">
        <template x-for="table in tables" :key="table.id">
            <div class="rounded-lg shadow-lg flex flex-col h-40 transition-transform duration-200 relative"
                 :class="tableStatusColor(table.status, 'bg')">

                <div class="p-4 flex flex-col items-center justify-center flex-grow text-center cursor-pointer transform hover:scale-105" 
                     :class="tableStatusColor(table.status, 'text')">
                    <div class="text-2xl font-bold" x-text="table.name"></div>
                    <div class="text-sm" x-text="'Cap: ' + table.capacity"></div>
                    <div class="text-xs font-semibold uppercase mt-2" x-text="table.status.replace('_', ' ')"></div>
                    <div class="text-xs mt-1" x-show="table.status_duration_minutes !== null">
                        <span x-text="`(${formatDuration(table.status_duration_minutes)})`"></span>
                    </div>
                </div>

                <div class="p-1 bg-gray-100 dark:bg-gray-700 text-center" x-show="table.base_status === 'OCCUPIED' || table.status !== 'AVAILABLE'">
                    <button @click="freeUpTable(table.id)" 
                            class="w-full text-xs px-2 py-1 rounded bg-green-600 hover:bg-green-700 text-white transition-colors">
                        Mark Available
                    </button>
                </div>
            </div>
        </template>
    </div>
</div>

<script>
function tableLayout() {
    return {
        tables: [],
        init() {
            this.fetchTables();
            setInterval(() => this.fetchTables(), 7000); // Poll every 7 seconds
        },
        fetchTables() {
            fetch('<?= base_url("api/get_table_status") ?>')
                .then(res => res.json())
                .then(data => {
                    this.tables = data;
                });
        },
        tableStatusColor(status, type) {
            const colors = {
                'AVAILABLE': 'bg-brand-600 text-white',
                'OCCUPIED': 'bg-brand-800 text-white',
                'ORDERING': 'bg-amber-400 text-black',
                'COOKING': 'bg-amber-600 text-white',
                'READY': 'bg-emerald-500 text-white',
                'SERVED': 'bg-emerald-700 text-white',
                'CLEANING': 'bg-gray-300 text-brand-800',
            };
            const colorClass = colors[status] || 'bg-gray-200 text-black';
            const parts = colorClass.split(' ');
            if (type === 'bg') return parts[0];
            if (type === 'text') return parts[1];
            return colorClass;
        },
        formatDuration(totalMinutes) {
            if (totalMinutes < 1) return '< 1m';
            if (totalMinutes < 60) {
                return `${totalMinutes}m`;
            }
            const hours = Math.floor(totalMinutes / 60);
            const minutes = totalMinutes % 60;
            return `${hours}h ${minutes}m`;
        },
        freeUpTable(tableId) {
            if (!confirm('Are you sure this table is now available? This will close any open order associated with it.')) {
                return;
            }

            fetch('<?= base_url("api/update_table_status") ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: tableId, status: 'AVAILABLE' })
            })
            .then(res => res.json())
            .then(response => {
                if (response.success) {
                    this.fetchTables(); // Refresh immediately
                } else {
                    alert('Failed to update table status. ' + (response.message || ''));
                }
            })
            .catch(() => {
                alert('A network error occurred.');
            });
        }
    }
}
</script>
