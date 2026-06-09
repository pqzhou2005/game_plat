<?php
namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_player_user_model_cannot_access_admin_panel(): void
    {
        $user = User::factory()->create(['status' => 1]);
        // User 模型没有 implement FilamentUser，所以 canAccessPanel 应该不可用
        $this->assertFalse(method_exists($user, 'canAccessPanel'),
            '玩家模型不应具备 canAccessPanel 方法');
    }

    public function test_admin_user_model_can_access_panel(): void
    {
        $admin = AdminUser::factory()->create([
            'username' => 'testadmin',
            'status' => 1,
        ]);

        $this->assertTrue($admin->status === 1);
        $this->assertTrue($admin->canAccessPanel(app(\Filament\Panel::class)));
    }

    public function test_disabled_admin_cannot_access_panel(): void
    {
        $admin = AdminUser::factory()->create([
            'username' => 'disabledadmin',
            'status' => 0,
        ]);

        $this->assertFalse($admin->canAccessPanel(app(\Filament\Panel::class)));
    }
}
