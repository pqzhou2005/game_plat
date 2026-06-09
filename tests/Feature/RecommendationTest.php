<?php
namespace Tests\Feature;

use App\Models\Recommendation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_recommendation_is_visible(): void
    {
        Recommendation::create([
            'position_code' => 'banner',
            'title' => '测试Banner',
            'target_type' => 'none',
            'status' => 1,
            'sort' => 0,
        ]);

        $items = Recommendation::active()->position('banner')->get();
        $this->assertCount(1, $items);
    }

    public function test_disabled_recommendation_is_hidden(): void
    {
        Recommendation::create([
            'position_code' => 'banner',
            'title' => '禁用Banner',
            'target_type' => 'none',
            'status' => 0,
            'sort' => 0,
        ]);

        $items = Recommendation::active()->position('banner')->get();
        $this->assertCount(0, $items);
    }

    public function test_recommendation_outside_time_range_is_hidden(): void
    {
        Recommendation::create([
            'position_code' => 'banner',
            'title' => '过期Banner',
            'target_type' => 'none',
            'status' => 1,
            'start_at' => now()->subDays(10),
            'end_at' => now()->subDays(5),
            'sort' => 0,
        ]);

        $items = Recommendation::active()->position('banner')->get();
        $this->assertCount(0, $items);
    }

    public function test_recommendation_within_time_range_is_visible(): void
    {
        Recommendation::create([
            'position_code' => 'banner',
            'title' => '有效Banner',
            'target_type' => 'none',
            'status' => 1,
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
            'sort' => 0,
        ]);

        $items = Recommendation::active()->position('banner')->get();
        $this->assertCount(1, $items);
    }
}
