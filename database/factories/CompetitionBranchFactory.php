<?php

namespace Database\Factories;

use App\Models\CompetitionBranch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompetitionBranch>
 */
class CompetitionBranchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Cabang '.fake()->unique()->word(),
            'is_open' => true,
            'sort_order' => fake()->numberBetween(10, 99),
        ];
    }
}
