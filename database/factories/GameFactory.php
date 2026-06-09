<?php
namespace Database\Factories;

use App\Models\Game;
use App\Models\GameCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameFactory extends Factory
{
    protected $model = Game::class;

    public function definition(): array
    {
        return [
            'category_id' => GameCategory::factory(),
            'name' => fake()->name(),
            'status' => 1,
        ];
    }
}
