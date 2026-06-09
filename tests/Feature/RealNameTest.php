<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_user_anti_addiction_status(): void
    {
        $user = User::factory()->create([
            'id_card_verified_at' => null,
            'id_card' => null,
        ]);

        $this->assertEquals(0, $user->getAntiAddictionStatus());
        $this->assertFalse($user->isRealNameVerified());
        $this->assertFalse($user->isAdult());
    }

    public function test_adult_user_anti_addiction_status(): void
    {
        $user = User::factory()->create([
            'id_card_verified_at' => now(),
            // 1988年出生，现在肯定＞18
            'id_card' => '110000198001010000',
        ]);

        $this->assertTrue($user->isRealNameVerified());
        $this->assertTrue($user->isAdult());
        $this->assertEquals(1, $user->getAntiAddictionStatus());
    }

    public function test_underage_user_anti_addiction_status(): void
    {
        $user = User::factory()->create([
            'id_card_verified_at' => now(),
            // 2020年出生，现在6岁
            'id_card' => '110000202001010000',
        ]);

        $this->assertTrue($user->isRealNameVerified());
        $this->assertFalse($user->isAdult());
        $this->assertEquals(2, $user->getAntiAddictionStatus());
    }

    public function test_id_card_mask(): void
    {
        $user = User::factory()->create([
            'id_card' => '110101199001011234',
        ]);

        $this->assertEquals('110101********1234', $user->masked_id_card);
    }
}
