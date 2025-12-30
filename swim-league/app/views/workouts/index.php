<div class="flex items-center justify-between mb-4">
  <h1 class="text-2xl font-bold">Program Latihan Saya</h1>
</div>

<div class="bg-white border rounded p-4">
  <table class="w-full text-left text-sm">
    <thead><tr><th class="p-2 border-b">Tanggal</th><th class="p-2 border-b">Judul</th><th class="p-2 border-b">Target</th><th class="p-2 border-b">Aksi</th></tr></thead>
    <tbody>
      <?php foreach ($list as $w): ?>
        <tr>
          <td class="p-2 border-b"><?= h($w['planned_on']) ?></td>
          <td class="p-2 border-b font-semibold"><?= h($w['title']) ?></td>
          <td class="p-2 border-b">
            <?= $w['target_distance_m'] ? (int)$w['target_distance_m'] . ' m' : '-' ?>
            <?php if ($w['stroke_type']): ?> · <?= h($w['stroke_type']) ?><?php endif; ?>
            <?php if ($w['target_swolf']): ?> · SWOLF <?= (int)$w['target_swolf'] ?><?php endif; ?>
          </td>
          <td class="p-2 border-b">
            <?php if ($w['status']==='completed'): ?>
              <span class="text-emerald-700">Selesai</span>
            <?php else: ?>
              <a class="text-blue-600" href="<?= h(BASE_URL) ?>/public/index.php?p=workout-complete&id=<?= (int)$w['id'] ?>">Tandai Selesai</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php if (empty($list)): ?><div class="text-gray-500">Belum ada program.</div><?php endif; ?>
</div>

