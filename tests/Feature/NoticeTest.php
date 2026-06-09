<?php
namespace Tests\Feature;

use App\Models\Notice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_notices_are_visible(): void
    {
        Notice::create([
            'title' => '发布公告',
            'type' => 'platform',
            'status' => 1,
            'published_at' => now()->subHour(),
        ]);

        $visible = Notice::active()->get();
        $this->assertCount(1, $visible);
    }

    public function test_unpublished_notice_is_hidden(): void
    {
        Notice::create([
            'title' => '未发布公告',
            'type' => 'platform',
            'status' => 0,
            'published_at' => now()->subHour(),
        ]);

        $visible = Notice::active()->get();
        $this->assertCount(0, $visible);
    }

    public function test_future_publish_notice_is_hidden(): void
    {
        Notice::create([
            'title' => '未来公告',
            'type' => 'platform',
            'status' => 1,
            'published_at' => now()->addDay(),
        ]);

        $visible = Notice::active()->get();
        $this->assertCount(0, $visible);
    }
}
