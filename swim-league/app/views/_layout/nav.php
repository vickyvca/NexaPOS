<?php $u = current_user(); ?>
<div class="rounded-xl overflow-hidden mb-6">
  <div class="bg-gradient-to-r from-sky-600 via-emerald-500 to-indigo-600 p-6">
    <div class="flex items-center justify-between">
      <div class="text-white font-bold text-xl">🏊‍♂️ Swim League</div>
      <div class="text-white/90">
        <?php if ($u): ?>Hai, <span class="font-semibold"><?= h($u['name']) ?></span><?php endif; ?>
      </div>
    </div>
  </div>
  <nav class="flex flex-wrap items-center gap-3 bg-white border-x border-b p-3">
    <a class="px-3 py-1 rounded hover:bg-gray-100" href="<?= h(BASE_URL) ?>/public/index.php?p=dashboard">Beranda</a>
    <a class="px-3 py-1 rounded hover:bg-gray-100" href="<?= h(BASE_URL) ?>/public/index.php?p=profile">Profil</a>
    <a class="px-3 py-1 rounded hover:bg-gray-100" href="<?= h(BASE_URL) ?>/public/index.php?p=activities">Aktivitas</a>
    <a class="px-3 py-1 rounded hover:bg-gray-100" href="<?= h(BASE_URL) ?>/public/index.php?p=meets">Pertandingan</a>
    <a class="px-3 py-1 rounded hover:bg-gray-100" href="<?= h(BASE_URL) ?>/public/index.php?p=leaderboard">Papan Peringkat</a>
    <a class="px-3 py-1 rounded hover:bg-gray-100" href="<?= h(BASE_URL) ?>/public/index.php?p=standings">Klasemen</a>
    <a class="px-3 py-1 rounded hover:bg-gray-100" href="<?= h(BASE_URL) ?>/public/index.php?p=achievements">Prestasi</a>
    <?php if ($u && in_array($u['role'], ['admin','organizer'])): ?>
      <span class="text-gray-400">|</span>
      <a class="px-3 py-1 rounded bg-gray-100" href="<?= h(BASE_URL) ?>/public/index.php?p=seasons">Musim</a>
      <a class="px-3 py-1 rounded bg-gray-100" href="<?= h(BASE_URL) ?>/public/index.php?p=events">Event</a>
      <a class="px-3 py-1 rounded bg-gray-100" href="<?= h(BASE_URL) ?>/public/index.php?p=meet-create">Tambah Pertandingan</a>
      <a class="px-3 py-1 rounded bg-gray-100" href="<?= h(BASE_URL) ?>/public/index.php?p=races">Lomba</a>
      <a class="px-3 py-1 rounded bg-gray-100" href="<?= h(BASE_URL) ?>/public/index.php?p=achievements-scan">Pindai Lencana</a>
    <?php endif; ?>
    <?php if ($u && in_array($u['role'], ['admin','organizer','coach'])): ?>
      <a class="px-3 py-1 rounded bg-gray-100" href="<?= h(BASE_URL) ?>/public/index.php?p=team">Tim</a>
      <a class="px-3 py-1 rounded bg-gray-100" href="<?= h(BASE_URL) ?>/public/index.php?p=coach">Pelatih</a>
      <a class="px-3 py-1 rounded bg-gray-100" href="<?= h(BASE_URL) ?>/public/index.php?p=coach-programs">Program</a>
    <?php endif; ?>
    <span class="flex-1"></span>
    <?php if ($u): ?>
      <a class="text-red-600 px-3 py-1 rounded hover:bg-red-50" href="<?= h(BASE_URL) ?>/public/index.php?p=logout">Keluar</a>
    <?php endif; ?>
    <a class="px-3 py-1 rounded hover:bg-gray-100" href="<?= h(BASE_URL) ?>/public/index.php?p=workouts">Latihan</a>
  </nav>
</div>
