<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AdminUser;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        AdminUser::firstOrCreate(
            ['username' => 'admin'],
            [
                'name' => '超级管理员',
                'email' => 'admin@602.com',
                'password' => 'admin123456',
                'role' => 'super_admin',
                'status' => 1,
            ]
        );
    }
}
