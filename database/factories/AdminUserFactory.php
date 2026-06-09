<?php
namespace Database\Factories;

use App\Models\AdminUser;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class AdminUserFactory extends Factory
{
    protected $model = AdminUser::class;

    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 1,
        ];
    }
}
