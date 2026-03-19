<?php

use App\Actions\CompareDailyTrending;
use App\Models\DailyTrending;
use App\Models\TrendingRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns new repos on date2 compared to date1', function () {
    TrendingRepository::factory()->create(['full_name' => 'octocat/a']);
    TrendingRepository::factory()->create(['full_name' => 'octocat/b']);

    DailyTrending::factory()->create(['full_name' => 'octocat/a', 'trending_date' => '2026-03-18']);
    DailyTrending::factory()->create(['full_name' => 'octocat/a', 'trending_date' => '2026-03-19']);
    DailyTrending::factory()->create(['full_name' => 'octocat/b', 'trending_date' => '2026-03-19']);

    $result = app(CompareDailyTrending::class)->handle('2026-03-18', '2026-03-19');

    expect($result)->toHaveCount(1)
        ->and($result->first()->full_name)->toBe('octocat/b');
});

it('returns empty when nothing is new', function () {
    TrendingRepository::factory()->create(['full_name' => 'octocat/a']);

    DailyTrending::factory()->create(['full_name' => 'octocat/a', 'trending_date' => '2026-03-18']);
    DailyTrending::factory()->create(['full_name' => 'octocat/a', 'trending_date' => '2026-03-19']);

    $result = app(CompareDailyTrending::class)->handle('2026-03-18', '2026-03-19');

    expect($result)->toBeEmpty();
});

it('treats date1 missing as everything on date2 being new', function () {
    TrendingRepository::factory()->create(['full_name' => 'octocat/a']);
    TrendingRepository::factory()->create(['full_name' => 'octocat/b']);

    DailyTrending::factory()->create(['full_name' => 'octocat/a', 'trending_date' => '2026-03-19']);
    DailyTrending::factory()->create(['full_name' => 'octocat/b', 'trending_date' => '2026-03-19']);

    $result = app(CompareDailyTrending::class)->handle('2026-03-18', '2026-03-19');

    expect($result->pluck('full_name')->all())->toMatchArray(['octocat/a', 'octocat/b']);
});

it('returns empty when date2 has no data', function () {
    TrendingRepository::factory()->create(['full_name' => 'octocat/a']);

    DailyTrending::factory()->create(['full_name' => 'octocat/a', 'trending_date' => '2026-03-18']);

    $result = app(CompareDailyTrending::class)->handle('2026-03-18', '2026-03-19');

    expect($result)->toBeEmpty();
});
