<h1 class="text-2xl font-bold mb-4">Ringkasan Teknik (SWOLF)</h1>

<?php if (empty($ath)): ?>
  <div class="text-gray-500">Profil atlet belum dibuat.</div>
<?php else: ?>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
    <div class="bg-white border rounded p-4">
      <h2 class="font-semibold mb-2">Rata-rata SWOLF per Panjang Kolam</h2>
      <canvas id="chartPl"></canvas>
    </div>
    <div class="bg-white border rounded p-4">
      <h2 class="font-semibold mb-2">Rata-rata SWOLF per Gaya</h2>
      <canvas id="chartSt"></canvas>
    </div>
  </div>
  <?php if (!empty($alerts)): ?>
  <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded p-3 mb-6">
    <div class="font-semibold mb-1">Peringatan SWOLF (30 hari terakhir)</div>
    <ul class="list-disc pl-6 text-sm">
      <?php foreach ($alerts as $al): ?>
        <li><?= h($al['stroke']) ?>: aktual <?= (int)$al['avg'] ?> > target <?= (int)$al['target'] ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>
  <div class="bg-white border rounded p-4 mb-6">
    <h2 class="font-semibold mb-2">Tren SWOLF 60 Hari Terakhir</h2>
    <canvas id="chartTrend"></canvas>
  </div>

  <div class="bg-white border rounded p-4">
    <h2 class="font-semibold mb-2">Target SWOLF per Gaya</h2>
    <?php if (!empty($ok)): ?><div class="text-green-700 mb-2"><?= h($ok) ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="text-red-600 mb-2"><?= h($error) ?></div><?php endif; ?>
    <form method="post" class="grid grid-cols-1 md:grid-cols-5 gap-3">
      <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
      <?php foreach (["FREE","BACK","BREAST","FLY","IM"] as $stt): ?>
        <label class="text-sm"><?= h($stt) ?>
          <input class="w-full border rounded px-3 py-2" type="number" min="1" name="target_<?= h($stt) ?>" value="<?= isset($targets[$stt]) ? (int)$targets[$stt] : '' ?>" placeholder="contoh 40">
        </label>
      <?php endforeach; ?>
      <div class="md:col-span-5">
        <button class="bg-blue-600 text-white px-4 py-2 rounded" type="submit">Simpan Target</button>
      </div>
    </form>
    <p class="text-xs text-gray-500 mt-2">Catatan: nilai SWOLF yang lebih rendah umumnya lebih efisien.</p>
  </div>

  <script>
    const plData = <?= json_encode($pl) ?>;
    const stData = <?= json_encode($st) ?>;
    const trendData = <?= json_encode($trend) ?>;
    const targetSeries = <?= json_encode(isset($target_series)?$target_series:[]) ?>;

    if (plData.length) {
      new Chart(document.getElementById('chartPl'), {
        type: 'bar',
        data: {
          labels: plData.map(x => x.pl + ' m'),
          datasets: [{ label: 'SWOLF', data: plData.map(x => x.avg_swolf), backgroundColor: '#34d399' }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
      });
    }
    if (stData.length) {
      new Chart(document.getElementById('chartSt'), {
        type: 'bar',
        data: {
          labels: stData.map(x => x.st || 'N/A'),
          datasets: [
            { label: 'SWOLF', data: stData.map(x => x.avg_swolf), backgroundColor: '#60a5fa' },
            { label: 'Target', data: targetSeries.map(x => x ?? null), type: 'line', borderColor: '#ef4444', borderWidth: 2, pointRadius: 0, spanGaps: true }
          ]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
      });
    }
    if (trendData.length) {
      new Chart(document.getElementById('chartTrend'), {
        type: 'line',
        data: {
          labels: trendData.map(x => x.d),
          datasets: [{ label: 'SWOLF', data: trendData.map(x => Number(x.avg_swolf).toFixed(0)), borderColor: '#f472b6', fill: false }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: false } } }
      });
    }
  </script>
<?php endif; ?>
