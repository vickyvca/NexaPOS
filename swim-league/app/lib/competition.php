<?php
require_once __DIR__ . '/db.php';

function assign_medals_for_race(int $race_id): void {
    $rows = DB::fetchAll('SELECT id, athlete_id, time_ms FROM results WHERE race_id=? AND status="OK" ORDER BY time_ms ASC', [$race_id]);
    $pos = 1;
    foreach ($rows as $r) {
        $medal = 'NONE'; $pts = 0;
        if ($pos === 1) { $medal = 'GOLD'; $pts = 25; }
        else if ($pos === 2) { $medal = 'SILVER'; $pts = 18; }
        else if ($pos === 3) { $medal = 'BRONZE'; $pts = 15; }
        else if ($pos >= 4 && $pos <= 8) { $medal = 'FINALIST'; $pts = 10; }
        try {
            DB::exec('UPDATE results SET medal=?, medal_points=? WHERE id=?', [$medal, $pts, $r['id']]);
        } catch (Throwable $e) {
            // Column may not exist; ignore persist and continue
        }
        $pos++;
    }
}

function reseed_lanes_simple(int $race_id): void {
    // Sort entries by best time in same swim_event (fastest first) then assign lane 1..N
    $race = DB::fetch('SELECT swim_event_id FROM races WHERE id=?', [$race_id]);
    if (!$race) return;
    $entries = DB::fetchAll('SELECT re.id, re.athlete_id FROM race_entries re WHERE re.race_id=?', [$race_id]);
    $ranked = [];
    foreach ($entries as $e) {
        $best = DB::fetch('SELECT MIN(r.time_ms) t FROM results r JOIN races rc ON r.race_id=rc.id WHERE r.athlete_id=? AND r.status="OK" AND rc.swim_event_id=?', [$e['athlete_id'], $race['swim_event_id']]);
        $ranked[] = ['id'=>$e['id'], 'athlete_id'=>$e['athlete_id'], 'seed'=>(int)($best['t'] ?? 0) ?: PHP_INT_MAX];
    }
    usort($ranked, function($a,$b){ return $a['seed'] <=> $b['seed']; });
    $lane = 1;
    foreach ($ranked as $row) {
        DB::exec('UPDATE race_entries SET lane=? WHERE id=?', [$lane++, $row['id']]);
    }
}

function generate_center_out_order(int $lanes): array {
    if ($lanes <= 0) return [];
    $order = [];
    if ($lanes % 2 === 0) {
        $left = $lanes / 2;      // center-left
        $right = $left + 1;      // center-right
        $order[] = $left;
        $order[] = $right;
        $offset = 1;
        while (count($order) < $lanes) {
            $l = $left - $offset;
            $r = $right + $offset;
            if ($l >= 1) $order[] = $l;
            if ($r <= $lanes) $order[] = $r;
            $offset++;
        }
    } else {
        $center = intdiv($lanes + 1, 2);
        $order[] = $center;
        $offset = 1;
        while (count($order) < $lanes) {
            $l = $center - $offset;
            $r = $center + $offset;
            if ($l >= 1) $order[] = $l;
            if ($r <= $lanes) $order[] = $r;
            $offset++;
        }
    }
    return $order;
}

function reseed_lanes_center_out(int $race_id, int $lanes = 8): void {
    $race = DB::fetch('SELECT swim_event_id FROM races WHERE id=?', [$race_id]);
    if (!$race) return;
    $entries = DB::fetchAll('SELECT re.id, re.athlete_id FROM race_entries re WHERE re.race_id=?', [$race_id]);
    if (!$entries) return;
    $ranked = [];
    foreach ($entries as $e) {
        $best = DB::fetch('SELECT MIN(r.time_ms) t FROM results r JOIN races rc ON r.race_id=rc.id WHERE r.athlete_id=? AND r.status="OK" AND rc.swim_event_id=?', [$e['athlete_id'], $race['swim_event_id']]);
        $ranked[] = ['id'=>$e['id'], 'athlete_id'=>$e['athlete_id'], 'seed'=>(int)($best['t'] ?? 0) ?: PHP_INT_MAX];
    }
    usort($ranked, function($a,$b){ return $a['seed'] <=> $b['seed']; });
    $count = count($ranked);
    $laneCount = max(min($lanes, $count), 1);
    $laneOrder = generate_center_out_order($laneCount);
    // Assign top N to available lanes by center-out order; if more than laneCount, continue sequentially
    $i = 0;
    foreach ($ranked as $idx => $row) {
        if ($i < $laneCount) {
            $lane = $laneOrder[$i];
        } else {
            $lane = $i + 1; // overflow sequential
        }
        DB::exec('UPDATE race_entries SET lane=? WHERE id=?', [$lane, $row['id']]);
        $i++;
    }
}

function split_heats_center_out(int $race_id, int $lanes = 8): void {
    $race = DB::fetch('SELECT * FROM races WHERE id=?', [$race_id]);
    if (!$race) return;
    $meet_id = (int)$race['meet_id'];
    $swim_event_id = (int)$race['swim_event_id'];
    $round_name = $race['round_name'];

    $entries = DB::fetchAll('SELECT re.id, re.athlete_id FROM race_entries re WHERE re.race_id=?', [$race_id]);
    if (!$entries) return;
    $ranked = [];
    foreach ($entries as $e) {
        $best = DB::fetch('SELECT MIN(r.time_ms) t FROM results r JOIN races rc ON r.race_id=rc.id WHERE r.athlete_id=? AND r.status="OK" AND rc.swim_event_id=?', [$e['athlete_id'], $swim_event_id]);
        $ranked[] = ['id'=>$e['id'], 'athlete_id'=>$e['athlete_id'], 'seed'=>(int)($best['t'] ?? 0) ?: PHP_INT_MAX];
    }
    usort($ranked, function($a,$b){ return $a['seed'] <=> $b['seed']; });
    $count = count($ranked);
    $laneCount = max(1, $lanes);
    $heats = (int)ceil($count / $laneCount);
    if ($heats <= 1) { reseed_lanes_center_out($race_id, $laneCount); return; }

    // Create additional heats: use existing race as the final heat (highest number)
    $finalHeatNo = $heats; // set existing as final heat
    DB::exec('UPDATE races SET heat_no=? WHERE id=?', [$finalHeatNo, $race_id]);
    $heatRaceIds = [];
    for ($h=1; $h<=$heats; $h++) {
        if ($h === $finalHeatNo) { $heatRaceIds[$h] = $race_id; continue; }
        DB::exec('INSERT INTO races(meet_id,swim_event_id,round_name,heat_no) VALUES (?,?,?,?)', [$meet_id, $swim_event_id, $round_name, $h]);
        $heatRaceIds[$h] = (int)DB::lastId();
    }

    // Distribute fastest to final heat, next to previous heat, etc.
    $laneOrder = generate_center_out_order($laneCount);
    $i = 0;
    foreach ($ranked as $row) {
        $heatIndexFromFastest = (int)floor($i / $laneCount); // 0..heats-1
        $heatNo = $heats - $heatIndexFromFastest; // fastest group to last heat
        if ($heatNo < 1) $heatNo = 1;
        $posInHeat = $i % $laneCount;
        $lane = $laneOrder[$posInHeat] ?? ($posInHeat + 1);
        $targetRaceId = $heatRaceIds[$heatNo];
        DB::exec('UPDATE race_entries SET race_id=?, lane=? WHERE id=?', [$targetRaceId, $lane, $row['id']]);
        $i++;
    }
}
