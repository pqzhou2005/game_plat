<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GameCategory;

class GameCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => '角色扮演', 'slug' => 'rpg', 'sort' => 1],
            ['name' => '策略游戏', 'slug' => 'strategy', 'sort' => 2],
            ['name' => '休闲竞技', 'slug' => 'casual', 'sort' => 3],
            ['name' => '模拟经营', 'slug' => 'simulation', 'sort' => 4],
            ['name' => '棋牌游戏', 'slug' => 'chess', 'sort' => 5],
            ['name' => '动作冒险', 'slug' => 'action', 'sort' => 6],
        ];

        foreach ($categories as $cat) {
            GameCategory::create($cat);
        }
    }
}
