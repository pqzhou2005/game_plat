<?php
namespace Tests\Feature;

use App\Models\Promote;
use App\Models\User;
use App\Models\UserAttribution;
use App\Services\PromotionAttributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionAttributionTest extends TestCase
{
    use RefreshDatabase;

    private PromotionAttributionService $service;
    private Promote $activePromote;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PromotionAttributionService::class);

        $this->activePromote = Promote::factory()->create([
            'promote_code' => 'a8K3xQ9m',
            'promote_name' => '百度-传奇A-落地页1',
            'promote_type' => 'landing',
            'status' => 1,
        ]);
    }

    /** @test */
    public function it_resolves_active_landing_promote()
    {
        $result = $this->service->resolvePromote('a8K3xQ9m');

        $this->assertNotNull($result);
        $this->assertEquals($this->activePromote->id, $result->id);
    }

    /** @test */
    public function it_returns_null_for_nonexistent_promote_code()
    {
        $result = $this->service->resolvePromote('not_exists');

        $this->assertNull($result);
    }

    /** @test */
    public function it_returns_null_for_disabled_promote()
    {
        $this->activePromote->update(['status' => 0]);

        $result = $this->service->resolvePromote('a8K3xQ9m');

        $this->assertNull($result);
    }

    /** @test */
    public function it_records_attribution_for_valid_promote()
    {
        $user = User::factory()->create();

        $this->service->recordRegisterAttribution($user, 'a8K3xQ9m');

        $this->assertDatabaseHas('user_attributions', [
            'user_id' => $user->id,
            'promote_id' => $this->activePromote->id,
            'promote_code' => 'a8K3xQ9m',
            'attribution_type' => 'landing',
        ]);
    }

    /** @test */
    public function it_does_not_record_attribution_when_promote_code_is_empty()
    {
        $user = User::factory()->create();

        $this->service->recordRegisterAttribution($user, null);
        $this->service->recordRegisterAttribution($user, '');

        $this->assertDatabaseCount('user_attributions', 0);
    }

    /** @test */
    public function it_does_not_record_attribution_for_invalid_promote_code()
    {
        $user = User::factory()->create();

        $this->service->recordRegisterAttribution($user, 'not_exists');

        $this->assertDatabaseCount('user_attributions', 0);
    }

    /** @test */
    public function it_does_not_duplicate_attribution_for_same_user()
    {
        $user = User::factory()->create();

        $this->service->recordRegisterAttribution($user, 'a8K3xQ9m');
        $this->service->recordRegisterAttribution($user, 'a8K3xQ9m');

        $this->assertDatabaseCount('user_attributions', 1);
    }

    /** @test */
    public function it_generates_unique_promote_code()
    {
        $code1 = PromotionAttributionService::generatePromoteCode();
        $code2 = PromotionAttributionService::generatePromoteCode();

        $this->assertNotEmpty($code1);
        $this->assertNotEmpty($code2);
        $this->assertNotEquals($code1, $code2);
        $this->assertGreaterThanOrEqual(8, strlen($code1));
        $this->assertLessThanOrEqual(12, strlen($code1));
    }

    /** @test */
    public function registration_with_valid_promote_code_creates_attribution()
    {
        $response = $this->post('/register', [
            'username' => 'attrib_test',
            'password' => '123456',
            'password_confirmation' => '123456',
            'promote_code' => 'a8K3xQ9m',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['username' => 'attrib_test']);

        $user = User::where('username', 'attrib_test')->first();
        $this->assertDatabaseHas('user_attributions', [
            'user_id' => $user->id,
            'promote_code' => 'a8K3xQ9m',
        ]);
    }

    /** @test */
    public function registration_without_promote_code_still_succeeds()
    {
        $response = $this->post('/register', [
            'username' => 'normal_reg',
            'password' => '123456',
            'password_confirmation' => '123456',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['username' => 'normal_reg']);
        $this->assertDatabaseCount('user_attributions', 0);
    }

    /** @test */
    public function registration_with_invalid_promote_code_still_succeeds()
    {
        $response = $this->post('/register', [
            'username' => 'bad_code_reg',
            'password' => '123456',
            'password_confirmation' => '123456',
            'promote_code' => 'not_exists',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['username' => 'bad_code_reg']);
        $this->assertDatabaseCount('user_attributions', 0);
    }

    /** @test */
    public function landing_page_returns_200_for_active_promote()
    {
        $response = $this->get('/p/a8K3xQ9m');

        $response->assertStatus(200);
    }

    /** @test */
    public function landing_page_returns_404_for_nonexistent_promote_code()
    {
        $response = $this->get('/p/not_exists');

        $response->assertStatus(404);
    }

    /** @test */
    public function landing_page_returns_404_for_disabled_promote()
    {
        $this->activePromote->update(['status' => 0]);

        $response = $this->get('/p/a8K3xQ9m');

        $response->assertStatus(404);
    }

    /** @test */
    public function landing_page_supports_registration_with_hidden_promote_code()
    {
        $response = $this->post('/register', [
            'username' => 'landing_user',
            'password' => '123456',
            'password_confirmation' => '123456',
            'mobile' => '13800138001',
            'promote_code' => 'a8K3xQ9m',
        ]);

        $response->assertRedirect();

        $user = User::where('username', 'landing_user')->first();
        $this->assertNotNull($user);
        $this->assertDatabaseHas('user_attributions', [
            'user_id' => $user->id,
            'promote_id' => $this->activePromote->id,
            'promote_code' => 'a8K3xQ9m',
        ]);
    }
}
