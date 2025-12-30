
<div class="bg-white shadow-card border border-brand-100 rounded-xxl p-4 sm:p-6 mb-6">
    <form method="GET" action="<?= base_url('hr/attendance') ?>">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="start_date" class="block text-sm font-medium text-brand-700">Tanggal Mulai</label>
                <input type="date" name="start_date" id="start_date" value="<?= htmlspecialchars($filters['start_date']) ?>" class="mt-1 block w-full px-3 py-3 border border-brand-200 rounded-xl text-brand-900">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-brand-700">Tanggal Selesai</label>
                <input type="date" name="end_date" id="end_date" value="<?= htmlspecialchars($filters['end_date']) ?>" class="mt-1 block w-full px-3 py-3 border border-brand-200 rounded-xl text-brand-900">
            </div>
            <div>
                <label for="employee_id" class="block text-sm font-medium text-brand-700">Karyawan</label>
                <select name="employee_id" id="employee_id" class="mt-1 block w-full px-3 py-3 border border-brand-200 rounded-xl text-brand-900">
                    <option value="all">Semua Karyawan</option>
                    <?php foreach ($employees as $employee): ?>
                        <option value="<?= $employee['id'] ?>" <?= $filters['employee_id'] == $employee['id'] ? 'selected' : '' ?>><?= htmlspecialchars($employee['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="px-4 py-3 bg-brand-600 text-white rounded-full hover:bg-brand-700 font-semibold">Saring</button>
                <a href="<?= base_url('hr/attendance?' . http_build_query(array_merge($filters, ['export' => 'csv']))) ?>" class="px-4 py-3 bg-emerald-600 text-white rounded-full hover:bg-emerald-700 font-semibold">Ekspor CSV</a>
            </div>
        </div>
    </form>
</div>

<!-- Table -->
<div class="flex flex-col">
    <div class="-my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="py-2 align-middle inline-block min-w-full sm:px-6 lg:px-8">
            <div class="shadow-card overflow-hidden border border-brand-100 sm:rounded-xxl">
                <table class="min-w-full divide-y divide-brand-100">
                    <thead class="bg-brand-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-brand-700 uppercase tracking-wider">Tanggal</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-brand-700 uppercase tracking-wider">Karyawan</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-brand-700 uppercase tracking-wider">Masuk</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-brand-700 uppercase tracking-wider">Pulang</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-brand-700 uppercase tracking-wider">Durasi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-brand-100">
                        <?php if (empty($report_data)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-brand-600">Tidak ada data untuk periode ini.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($report_data as $row): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-brand-700"><?= htmlspecialchars($row['date']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-brand-900"><?= htmlspecialchars($row['name']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-brand-700"><?= $row['clock_in'] ? htmlspecialchars(date('H:i:s', strtotime($row['clock_in']))) : '-' ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-brand-700"><?= $row['clock_out'] ? htmlspecialchars(date('H:i:s', strtotime($row['clock_out']))) : '-' ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-brand-700"><?= $row['duration'] ?? '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
