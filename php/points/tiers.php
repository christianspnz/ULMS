<?php
function getTierForPoints(int $points): array
{
    // Must be sorted ascending by 'min'
    $tiers = [
        [
            'name' => 'Novice',
            'min' => 0,
            'color' => '#94a3b8',
            'icon' => 'fa-seedling'
        ],
        [
            'name' => 'Rookie',
            'min' => 300,
            'color' => '#60a5fa',
            'icon' => 'fa-shield-halved'
        ],
        [
            'name' => 'Veteran',
            'min' => 600,
            'color' => '#34d399',
            'icon' => 'fa-medal'
        ],
        [
            'name' => 'Expert',
            'min' => 900,
            'color' => '#a78bfa',
            'icon' => 'fa-chess-knight'
        ],
        [
            'name' => 'Master',
            'min' => 1500,
            'color' => '#fbbf24',
            'icon' => 'fa-crown'
        ],
        [
            'name' => 'Legend',
            'min' => 3000,
            'color' => '#f87171',
            'icon' => 'fa-fire'
        ],
    ];

    $idx = 0;
    foreach ($tiers as $i => $t) {
        if ($points >= $t['min']) $idx = $i;
    }

    $current = $tiers[$idx];
    $next    = $tiers[$idx + 1] ?? null;

    return [
        'name'             => $current['name'],
        'icon'             => $current['icon'],
        'color'            => $current['color'],
        'next_tier_name'   => $next['name'] ?? null,
        'points_to_next'   => $next ? $next['min'] - $points : 0,
        'progress_percent' => $next
            ? (int) round((($points - $current['min']) / ($next['min'] - $current['min'])) * 100)
            : 100,
    ];
}
