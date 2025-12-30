<?php

// This is the actual view file for the dashboard.
?>

<div>

    <div class="space-y-6">
        <!-- Metrics Cards -->
        <div>
            <h3 class="text-lg font-extrabold text-brand-800">Ringkasan</h3>
            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach (($cards ?? []) as $c): ?>
                <div class="bg-white rounded-xxl shadow-card border border-brand-100 p-4">
                    <div class="text-sm text-brand-700 mb-1"><?= htmlspecialchars($c['label']) ?></div>
                    <div class="text-2xl font-extrabold text-brand-900"><?= htmlspecialchars($c['value']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Trends: 7-day revenue and order counts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-white rounded-xxl shadow-card border border-brand-100 p-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-brand-800 font-bold">Omzet 7 Hari</h4>
                </div>
                <div class="space-y-2">
                    <?php foreach ($daily_trend as $date => $v): ?>
                        <?php $pct = $max_rev > 0 ? round(($v['rev'] / $max_rev) * 100) : 0; ?>
                        <div>
                            <div class="flex justify-between text-xs text-brand-700">
                                <span><?= htmlspecialchars(date('D, d M', strtotime($date))) ?></span>
                                <span>Rp <?= number_format($v['rev'], 0, ',', '.') ?></span>
                            </div>
                            <div class="w-full h-2 bg-brand-50 rounded-full overflow-hidden">
                                <div class="h-2 bg-brand-600" style="width: <?= $pct ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="bg-white rounded-xxl shadow-card border border-brand-100 p-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-brand-800 font-bold">Jumlah Order 7 Hari</h4>
                </div>
                <div class="space-y-2">
                    <?php foreach ($daily_trend as $date => $v): ?>
                        <?php $pct2 = $max_cnt > 0 ? round(($v['cnt'] / $max_cnt) * 100) : 0; ?>
                        <div>
                            <div class="flex justify-between text-xs text-brand-700">
                                <span><?= htmlspecialchars(date('D, d M', strtotime($date))) ?></span>
                                <span><?= number_format($v['cnt'], 0, ',', '.') ?> order</span>
                            </div>
                            <div class="w-full h-2 bg-brand-50 rounded-full overflow-hidden">
                                <div class="h-2 bg-emerald-500" style="width: <?= $pct2 ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- AOV & Perbandingan -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-xxl shadow-card border border-brand-100 p-4">
                <div class="text-sm text-brand-700">AOV Hari Ini</div>
                <div class="text-2xl font-extrabold text-brand-900">Rp <?= number_format($aov_today ?? 0, 0, ',', '.') ?></div>
            </div>
            <div class="bg-white rounded-xxl shadow-card border border-brand-100 p-4">
                <div class="text-sm text-brand-700">AOV Minggu Ini</div>
                <div class="text-2xl font-extrabold text-brand-900">Rp <?= number_format($aov_week ?? 0, 0, ',', '.') ?></div>
            </div>
            <div class="bg-white rounded-xxl shadow-card border border-brand-100 p-4">
                <div class="text-sm text-brand-700">Omzet vs Kemarin</div>
                <?php 
                  $deltaPct = isset($rev_delta_pct) && $rev_delta_pct !== null ? round($rev_delta_pct, 1) : null;
                  $dir = ($deltaPct === null) ? 'neutral' : ($deltaPct >= 0 ? 'up' : 'down');
                ?>
                <div class="flex items-end gap-2">
                    <div class="text-2xl font-extrabold text-brand-900">
                        <?= ($rev_delta_abs >= 0 ? '+' : '−') ?>Rp <?= number_format(abs($rev_delta_abs ?? 0), 0, ',', '.') ?>
                    </div>
                    <div class="text-sm <?= $dir==='up' ? 'text-emerald-600' : ($dir==='down' ? 'text-red-600' : 'text-brand-700') ?>">
                        <?= $deltaPct === null ? 'N/A' : (($deltaPct >= 0 ? '+' : '−') . number_format(abs($deltaPct), 1, ',', '.') . '%') ?>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xxl shadow-card border border-brand-100 p-4">
                <div class="text-sm text-brand-700">Order vs Kemarin</div>
                <?php 
                  $oDeltaPct = isset($orders_delta_pct) && $orders_delta_pct !== null ? round($orders_delta_pct, 1) : null;
                  $oDir = ($oDeltaPct === null) ? 'neutral' : ($oDeltaPct >= 0 ? 'up' : 'down');
                ?>
                <div class="flex items-end gap-2">
                    <div class="text-2xl font-extrabold text-brand-900">
                        <?= ($orders_delta_abs >= 0 ? '+' : '−') ?><?= number_format(abs($orders_delta_abs ?? 0), 0, ',', '.') ?> order
                    </div>
                    <div class="text-sm <?= $oDir==='up' ? 'text-emerald-600' : ($oDir==='down' ? 'text-red-600' : 'text-brand-700') ?>">
                        <?= $oDeltaPct === null ? 'N/A' : (($oDeltaPct >= 0 ? '+' : '−') . number_format(abs($oDeltaPct), 1, ',', '.') . '%') ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top 5 Menu Hari Ini -->
        <div class="bg-white rounded-xxl shadow-card border border-brand-100 p-4">
            <div class="flex items-center justify-between mb-2">
                <h4 class="text-brand-800 font-bold">Top 5 Menu Hari Ini</h4>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-brand-100">
                    <thead class="bg-brand-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-brand-700 uppercase">Menu</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-brand-700 uppercase">Qty</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-brand-700 uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-brand-100">
                        <?php if (empty($top_menus_today ?? [])): ?>
                            <tr><td colspan="3" class="px-4 py-4 text-center text-brand-600">Belum ada penjualan hari ini.</td></tr>
                        <?php else: foreach ($top_menus_today as $row): ?>
                            <tr>
                                <td class="px-4 py-2 text-sm text-brand-900"><?= htmlspecialchars($row['name']) ?></td>
                                <td class="px-4 py-2 text-sm text-right text-brand-700"><?= number_format($row['qty'], 0, ',', '.') ?></td>
                                <td class="px-4 py-2 text-sm text-right font-semibold">Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Per Cabang (Hari Ini) -->
        <?php if (!empty($branches_overview ?? [])): ?>
        <div class="bg-white rounded-xxl shadow-card border border-brand-100 p-4">
            <div class="flex items-center justify-between mb-2">
                <h4 class="text-brand-800 font-bold">Ringkasan Per Cabang (Hari Ini)</h4>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-brand-100">
                    <thead class="bg-brand-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-brand-700 uppercase">Cabang</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-brand-700 uppercase">Order</th>
                            <th class="px-4 py-2 text-right text-xs font-semibold text-brand-700 uppercase">Omzet</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-brand-100">
                        <?php foreach ($branches_overview as $b): ?>
                        <tr>
                            <td class="px-4 py-2 text-sm text-brand-900"><?= htmlspecialchars($b['name']) ?></td>
                            <td class="px-4 py-2 text-sm text-right text-brand-700"><?= number_format($b['orders_today'], 0, ',', '.') ?></td>
                            <td class="px-4 py-2 text-sm text-right font-semibold">Rp <?= number_format($b['rev_today'], 0, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="bg-brand-50">
                            <td class="px-4 py-2 text-sm font-bold text-brand-900">Total</td>
                            <td class="px-4 py-2 text-sm text-right font-bold text-brand-900"><?= number_format(array_sum(array_column($branches_overview, 'orders_today')), 0, ',', '.') ?></td>
                            <td class="px-4 py-2 text-sm text-right font-bold text-brand-900">Rp <?= number_format(array_sum(array_column($branches_overview, 'rev_today')), 0, ',', '.') ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Top 5 Kategori Hari Ini & AOV per Channel -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="bg-white rounded-xxl shadow-card border border-brand-100 p-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-brand-800 font-bold">Top 5 Kategori Hari Ini</h4>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-brand-100">
                        <thead class="bg-brand-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-brand-700 uppercase">Kategori</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-brand-700 uppercase">Qty</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-brand-700 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-brand-100">
                            <?php if (empty($top_categories_today ?? [])): ?>
                                <tr><td colspan="3" class="px-4 py-4 text-center text-brand-600">Belum ada penjualan hari ini.</td></tr>
                            <?php else: foreach ($top_categories_today as $row): ?>
                                <tr>
                                    <td class="px-4 py-2 text-sm text-brand-900"><?= htmlspecialchars($row['name']) ?></td>
                                    <td class="px-4 py-2 text-sm text-right text-brand-700"><?= number_format($row['qty'], 0, ',', '.') ?></td>
                                    <td class="px-4 py-2 text-sm text-right font-semibold">Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-xxl shadow-card border border-brand-100 p-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-brand-800 font-bold">AOV per Channel (Hari Ini)</h4>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-brand-100">
                        <thead class="bg-brand-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-semibold text-brand-700 uppercase">Channel</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-brand-700 uppercase">Order</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-brand-700 uppercase">Omzet</th>
                                <th class="px-4 py-2 text-right text-xs font-semibold text-brand-700 uppercase">AOV</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-brand-100">
                            <?php $channels = ['DINE_IN' => 'Dine In', 'TAKE_AWAY' => 'Take Away']; ?>
                            <?php foreach ($channels as $key => $label): $row = $aov_by_channel_today[$key] ?? ['cnt'=>0,'rev'=>0,'aov'=>0]; ?>
                                <tr>
                                    <td class="px-4 py-2 text-sm text-brand-900"><?= $label ?></td>
                                    <td class="px-4 py-2 text-sm text-right text-brand-700"><?= number_format($row['cnt'], 0, ',', '.') ?></td>
                                    <td class="px-4 py-2 text-sm text-right text-brand-700">Rp <?= number_format($row['rev'], 0, ',', '.') ?></td>
                                    <td class="px-4 py-2 text-sm text-right font-semibold">Rp <?= number_format($row['aov'], 0, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    
