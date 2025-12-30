<h1 class="text-2xl font-bold mb-4">My Achievements</h1>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
  <?php
    $defs = [
      'FIRST_RACE' => 'First official race finished',
      'FIRST_PODIUM' => 'First time on the podium (Top 3)',
      'SUB_60s_100_FREE' => 'Sub 60s in 100m Freestyle',
      '10K_MONTH' => 'Swim 10 km in a month',
    ];
    $owned = [];
    foreach ($list as $it) { $owned[$it['code']] = $it['granted_on']; }
  ?>
  <?php foreach ($defs as $code=>$desc): ?>
    <div class="rounded-xl border bg-white p-4">
      <div class="font-semibold mb-1"><?= h($code) ?></div>
      <div class="text-gray-600 text-sm mb-2"><?= h($desc) ?></div>
      <?php if (isset($owned[$code])): ?>
        <div class="text-emerald-700 text-sm">Earned on <?= h($owned[$code]) ?></div>
      <?php else: ?>
        <div class="text-gray-400 text-sm">Not yet earned</div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>

<div class="bg-white border rounded p-4">
  <h2 class="text-lg font-semibold mb-2">Medal Points (per race)</h2>
  <p class="text-sm text-gray-600 mb-3">Example guideline for event medals to points mapping:</p>
  <ul class="list-disc pl-6 text-sm">
    <li>Gold: 25 points</li>
    <li>Silver: 18 points</li>
    <li>Bronze: 15 points</li>
    <li>Finalist (4th–8th): 10 points</li>
  </ul>
  <p class="text-sm text-gray-500 mt-2">Leaderboard uses performance points (pseudo-FINA). Medal points can be used for team awards.</p>
</div>

