<?php

function h($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function ms_to_time(int $ms): string {
    $total_seconds = intdiv($ms, 1000);
    $centi = intdiv($ms % 1000, 10);
    $minutes = intdiv($total_seconds, 60);
    $seconds = $total_seconds % 60;
    return sprintf('%d:%02d.%02d', $minutes, $seconds, $centi);
}

function pace100_ms(int $duration_ms, int $distance_m): int {
    if ($distance_m <= 0) return 0;
    return (int) round(($duration_ms / $distance_m) * 100);
}

function pace100_str(int $duration_ms, int $distance_m): string {
    $ms = pace100_ms($duration_ms, $distance_m);
    return $ms ? ms_to_time($ms) : '-';
}

function age_group(string $birthdate, string $season_start): string {
    $b = new DateTime($birthdate);
    $s = new DateTime($season_start);
    $age = (int)$b->diff($s)->y;
    if ($age <= 12) return 'U12';
    if ($age <= 14) return 'U14';
    if ($age <= 16) return 'U16';
    if ($age <= 18) return 'U18';
    return 'Open';
}

function compute_points(int $swim_event_id, int $time_ms): int {
    // Simple baseline map (ms) if no DB table is used
    $baseline = [
        // key by swim_event_id for demo; adjust to your data
        1 => 30000, // e.g., 50m freestyle baseline 30.00s
    ];
    $base = $baseline[$swim_event_id] ?? 60000; // default 60s
    if ($time_ms <= 0) return 0;
    $points = (int) round(1000 * pow(($base / $time_ms), 3));
    return max(0, $points);
}

