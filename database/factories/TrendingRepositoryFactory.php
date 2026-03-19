<?php

namespace Database\Factories;

use App\Models\TrendingRepository;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrendingRepository>
 */
class TrendingRepositoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->slug(2);

        return [
            'github_id' => fake()->unique()->numberBetween(1_000, 9_999_999),
            'name' => $name,
            'full_name' => fake()->unique()->userName().'/'.$name,
            'owner' => fake()->userName(),
            'description' => fake()->optional()->sentence(),
            'language' => fake()->optional()->randomElement(['PHP', 'TypeScript', 'Go', 'Python']),
            'stars_count' => fake()->numberBetween(0, 100_000),
            'forks_count' => fake()->numberBetween(0, 25_000),
            'open_issues_count' => fake()->numberBetween(0, 10_000),
            'html_url' => fake()->url(),
            'github_created_at' => now()->subDays(fake()->numberBetween(0, 30)),
            'fetched_at' => now(),
        ];
    }
}
