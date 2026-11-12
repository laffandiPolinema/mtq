<?php

namespace Database\Factories;

use App\Models\CompetitionBranch;
use App\Models\CompetitionCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompetitionCategory>
 */
class CompetitionCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'competition_branch_id' => CompetitionBranch::factory(),
            'name' => 'Kategori '.fake()->unique()->word(),
            'description' => 'Tingkat SD/MI',
            'fee' => 40000,
            'max_age' => null,
            'is_open' => true,
            'sort_order' => 1,
        ];
    }
}
