<?php
namespace Database\Factories;

use App\Models\PaymentOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentOrderFactory extends Factory
{
    protected $model = PaymentOrder::class;

    public function definition(): array
    {
        return [
            'order_no' => date('YmdHis') . Str::random(8),
            'user_id' => User::factory(),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'status' => 'pending',
        ];
    }
}
