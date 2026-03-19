<?php

namespace Database\Factories;

use App\Models\DailyTrending;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyTrending>
 */
class DailyTrendingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->userName().'/'.fake()->slug(2),
            'trending_date' => now()->toDateString(),
        ];
    }
}
