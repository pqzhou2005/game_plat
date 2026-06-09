<?php
namespace Database\Factories;

use App\Models\GameCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameCategoryFactory extends Factory
{
    protected $model = GameCategory::class;

    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'slug' => fake()->unique()->slug(),
            'status' => 1,
        ];
    }
}
