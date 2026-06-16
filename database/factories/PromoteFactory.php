<?php
namespace Database\Factories;

use App\Models\Promote;
use App\Services\PromotionAttributionService;
use Illuminate\Database\Eloquent\Factories\Factory;

class PromoteFactory extends Factory
{
    protected $model = Promote::class;

    public function definition(): array
    {
        return [
            'promote_code' => PromotionAttributionService::generatePromoteCode(),
            'promote_name' => fake()->unique()->company() . '-落地页',
            'promote_type' => 'landing',
            'status' => 1,
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 0,
        ]);
    }
}
