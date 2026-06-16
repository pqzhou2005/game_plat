<?php
namespace Tests\Feature;

use App\Models\Game;
use App\Models\PaymentOrder;
use App\Models\Promote;
use App\Models\RoleReport;
use App\Models\User;
use App\Models\UserAttribution;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PromotePerformanceTest extends TestCase
{
    use RefreshDatabase;

    private Promote $promoteA;
    private Promote $promoteB;
    private Game $game;

    protected function setUp(): void
    {
        parent::setUp();

        $this->game = Game::factory()->create(['name' => '测试游戏']);

        $this->promoteA = Promote::factory()->create([
            'promote_name' => '百度推广',
            'promote_code' => 'test_a',
            'game_id' => $this->game->id,
        ]);

        $this->promoteB = Promote::factory()->create([
            'promote_name' => '谷歌推广',
            'promote_code' => 'test_b',
            'game_id' => $this->game->id,
        ]);
    }

    /**
     * 执行与 PromotePerformance::loadReportData() 相同的聚合 SQL
     */
    private function fetchReport(array $overrides = []): array
    {
        if (isset($overrides['start_date'])) {
            $start = Carbon::parse($overrides['start_date'])->startOfDay()->format('Y-m-d H:i:s');
        } else {
            $start = now()->subDays(29)->startOfDay()->format('Y-m-d H:i:s');
        }

        if (isset($overrides['end_date'])) {
            $end = Carbon::parse($overrides['end_date'])->endOfDay()->format('Y-m-d H:i:s');
        } else {
            $end = now()->endOfDay()->format('Y-m-d H:i:s');
        }

        $gameId = $overrides['game_id'] ?? null;
        $promoteId = $overrides['promote_id'] ?? null;

        // 子查询1: 归因用户
        $attributedUsers = DB::table('user_attributions')
            ->select('user_id', 'promote_id')
            ->whereBetween('created_at', [$start, $end]);

        // 子查询2: 创角统计
        $roleStats = DB::table('role_reports')
            ->select('user_id',
                DB::raw('COUNT(DISTINCT CONCAT(game_id, "-", server_id, "-", role_id)) as role_count')
            )
            ->where('submit_type', 1)
            ->groupBy('user_id');

        // 子查询3: 付费统计
        $paymentStats = DB::table('payment_orders')
            ->select('user_id',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('COALESCE(SUM(amount), 0) as revenue')
            )
            ->where('status', 'success')
            ->groupBy('user_id');

        // 主查询
        $results = DB::table('promotes as p')
            ->select(
                'p.id',
                'p.promote_code',
                'p.promote_name',
                'p.game_id',
                'g.name as game_name',
                DB::raw('COUNT(DISTINCT au.user_id) as register_count'),
                DB::raw('COALESCE(COUNT(DISTINCT rs.user_id), 0) as role_user_count'),
                DB::raw('COALESCE(SUM(rs.role_count), 0) as role_count'),
                DB::raw('COALESCE(COUNT(DISTINCT ps.user_id), 0) as pay_user_count'),
                DB::raw('COALESCE(SUM(ps.order_count), 0) as order_count'),
                DB::raw('COALESCE(SUM(ps.revenue), 0) as revenue')
            )
            ->leftJoinSub($attributedUsers, 'au', 'au.promote_id', '=', 'p.id')
            ->leftJoinSub($roleStats, 'rs', 'rs.user_id', '=', 'au.user_id')
            ->leftJoinSub($paymentStats, 'ps', 'ps.user_id', '=', 'au.user_id')
            ->leftJoin('games as g', 'g.id', '=', 'p.game_id')
            ->when($gameId, fn($q) => $q->where('p.game_id', $gameId))
            ->when($promoteId, fn($q) => $q->where('p.id', $promoteId))
            ->groupBy('p.id', 'p.promote_code', 'p.promote_name', 'p.game_id', 'g.name')
            ->orderBy('register_count', 'desc')
            ->get();

        return $results->map(fn($r) => [
            'id' => $r->id,
            'promote_code' => $r->promote_code,
            'promote_name' => $r->promote_name,
            'register_count' => (int) $r->register_count,
            'role_user_count' => (int) $r->role_user_count,
            'role_count' => (int) $r->role_count,
            'pay_user_count' => (int) $r->pay_user_count,
            'order_count' => (int) $r->order_count,
            'revenue' => (float) $r->revenue,
        ])->toArray();
    }

    // ─── Helpers ───────────────────────────────────────

    private function attributeUser(User $user, Promote $promote, ?Carbon $createdAt = null): UserAttribution
    {
        return UserAttribution::create([
            'user_id' => $user->id,
            'promote_id' => $promote->id,
            'promote_code' => $promote->promote_code,
            'attribution_type' => 'landing',
            'created_at' => $createdAt ?? now(),
        ]);
    }

    private function createRole(User $user, array $overrides = []): RoleReport
    {
        static $roleSeq = 0;
        return RoleReport::create(array_merge([
            'user_id' => $user->id,
            'game_id' => $this->game->id,
            'submit_type' => 1,
            'server_id' => '1',
            'server_name' => '测试服',
            'role_id' => (string) (++$roleSeq),
            'role_name' => '角色' . $roleSeq,
        ], $overrides));
    }

    private function createOrder(User $user, float $amount, string $status = 'success'): PaymentOrder
    {
        return PaymentOrder::create([
            'order_no' => 'T' . uniqid(),
            'user_id' => $user->id,
            'game_id' => $this->game->id,
            'amount' => $amount,
            'status' => $status,
            'paid_at' => $status === 'success' ? now() : null,
        ]);
    }

    // ─── 核心测试：防止 JOIN 放大 ──────────────────

    /** @test */
    public function 不会因多角色多订单而放大充值金额()
    {
        // 1 个用户，2 个角色，3 笔订单
        // 如果 SQL 有交叉放大，revenue 会变成 30+50+70 的 N 倍
        $user = User::factory()->create();
        $this->attributeUser($user, $this->promoteA);

        $this->createRole($user, ['server_id' => 's1', 'role_id' => 'r1']);
        $this->createRole($user, ['server_id' => 's2', 'role_id' => 'r2']);

        $this->createOrder($user, 30.00);
        $this->createOrder($user, 50.00);
        $this->createOrder($user, 70.00);

        $report = $this->fetchReport();
        $row = collect($report)->firstWhere('id', $this->promoteA->id);

        $this->assertEquals(1, $row['register_count']);
        $this->assertEquals(1, $row['role_user_count']);
        $this->assertEquals(2, $row['role_count']);
        $this->assertEquals(1, $row['pay_user_count']);
        $this->assertEquals(3, $row['order_count']);
        $this->assertEquals(150.00, $row['revenue'], '充值金额 = 30+50+70，不被 JOIN 放大');
    }

    /** @test */
    public function 多用户多角色多订单金额仍然正确()
    {
        // 2 个用户，角色数和订单数不均匀，验证金额聚合正确
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();

        $this->attributeUser($u1, $this->promoteA);
        $this->attributeUser($u2, $this->promoteA);

        // u1: 3 roles, 1 order = 100
        $this->createRole($u1, ['role_id' => 'r1']);
        $this->createRole($u1, ['role_id' => 'r2']);
        $this->createRole($u1, ['role_id' => 'r3']);
        $this->createOrder($u1, 100.00);

        // u2: 1 role, 2 orders = 40+60
        $this->createRole($u2, ['role_id' => 'r4']);
        $this->createOrder($u2, 40.00);
        $this->createOrder($u2, 60.00);

        $report = $this->fetchReport();
        $row = collect($report)->firstWhere('id', $this->promoteA->id);

        $this->assertEquals(2, $row['register_count']);
        $this->assertEquals(2, $row['role_user_count']);
        $this->assertEquals(4, $row['role_count']);
        $this->assertEquals(2, $row['pay_user_count']);
        $this->assertEquals(3, $row['order_count']);
        $this->assertEquals(200.00, $row['revenue']);
    }

    // ─── 常规指标测试 ──────────────────────────────

    /** @test */
    public function 基础指标正确()
    {
        $user = User::factory()->create();
        $this->attributeUser($user, $this->promoteA);
        $this->createRole($user, ['role_id' => 'r1']);
        $this->createOrder($user, 50.00);

        $report = $this->fetchReport();
        $row = collect($report)->firstWhere('id', $this->promoteA->id);

        $this->assertEquals(1, $row['register_count']);
        $this->assertEquals(1, $row['role_user_count']);
        $this->assertEquals(1, $row['role_count']);
        $this->assertEquals(1, $row['pay_user_count']);
        $this->assertEquals(1, $row['order_count']);
        $this->assertEquals(50.00, $row['revenue']);
    }

    /** @test */
    public function 无注册的推广入口显示零()
    {
        $report = $this->fetchReport();
        $row = collect($report)->firstWhere('id', $this->promoteB->id);

        $this->assertNotNull($row);
        $this->assertEquals(0, $row['register_count']);
        $this->assertEquals(0, $row['role_user_count']);
        $this->assertEquals(0, $row['role_count']);
        $this->assertEquals(0, $row['pay_user_count']);
        $this->assertEquals(0, $row['order_count']);
        $this->assertEquals(0.0, $row['revenue']);
    }

    /** @test */
    public function 不同推广入口数据不串()
    {
        $u1 = User::factory()->create();
        $u2 = User::factory()->create();
        $this->attributeUser($u1, $this->promoteA);
        $this->attributeUser($u2, $this->promoteB);
        $this->createOrder($u1, 100.00);
        $this->createOrder($u2, 200.00);

        $report = $this->fetchReport();

        $rowA = collect($report)->firstWhere('id', $this->promoteA->id);
        $rowB = collect($report)->firstWhere('id', $this->promoteB->id);

        $this->assertEquals(100.00, $rowA['revenue']);
        $this->assertEquals(200.00, $rowB['revenue']);
    }

    // ─── 筛选测试 ──────────────────────────────────

    /** @test */
    public function 按日期范围过滤归因用户()
    {
        $user = User::factory()->create();
        // 60 天前的归因，不应该出现在默认 30 天范围内
        $this->attributeUser($user, $this->promoteA, now()->subDays(60));
        $this->createOrder($user, 50.00);

        $report = $this->fetchReport();
        $this->assertEquals(0,
            collect($report)->firstWhere('id', $this->promoteA->id)['register_count']
        );
    }

    /** @test */
    public function 自定义日期范围可以查到历史归因()
    {
        $user = User::factory()->create();
        $this->attributeUser($user, $this->promoteA, now()->subDays(45));
        $this->createOrder($user, 88.00);

        $report = $this->fetchReport([
            'start_date' => now()->subDays(50),
            'end_date' => now()->subDays(40),
        ]);
        $row = collect($report)->firstWhere('id', $this->promoteA->id);

        $this->assertEquals(1, $row['register_count']);
        $this->assertEquals(88.00, $row['revenue']);
    }

    /** @test */
    public function 按游戏筛选()
    {
        $user = User::factory()->create();
        $this->attributeUser($user, $this->promoteA);

        $report = $this->fetchReport(['game_id' => 99999]);
        $this->assertEmpty($report);
    }

    /** @test */
    public function 按推广入口筛选()
    {
        $user = User::factory()->create();
        $this->attributeUser($user, $this->promoteA);
        $this->attributeUser(User::factory()->create(), $this->promoteB);

        $report = $this->fetchReport(['promote_id' => $this->promoteA->id]);

        $this->assertCount(1, $report);
        $this->assertEquals($this->promoteA->id, $report[0]['id']);
    }

    // ─── 口径过滤测试 ──────────────────────────────

    /** @test */
    public function 非创角上报不计入创角数()
    {
        $user = User::factory()->create();
        $this->attributeUser($user, $this->promoteA);
        $this->createRole($user, ['role_id' => 'r1', 'submit_type' => 2]); // 升级，不是创角

        $report = $this->fetchReport();
        $row = collect($report)->firstWhere('id', $this->promoteA->id);

        $this->assertEquals(0, $row['role_user_count']);
        $this->assertEquals(0, $row['role_count']);
    }

    /** @test */
    public function 非成功订单不计入付费()
    {
        $user = User::factory()->create();
        $this->attributeUser($user, $this->promoteA);

        $this->createOrder($user, 999.00, 'pending');
        $this->createOrder($user, 888.00, 'failed');
        $this->createOrder($user, 777.00, 'closed');

        $report = $this->fetchReport();
        $row = collect($report)->firstWhere('id', $this->promoteA->id);

        $this->assertEquals(0, $row['pay_user_count']);
        $this->assertEquals(0, $row['order_count']);
        $this->assertEquals(0.0, $row['revenue']);
    }

    // ─── 排序测试 ──────────────────────────────────

    /** @test */
    public function 默认按注册人数降序排列()
    {
        $this->attributeUser(User::factory()->create(), $this->promoteA);
        $this->attributeUser(User::factory()->create(), $this->promoteA);
        $this->attributeUser(User::factory()->create(), $this->promoteB);

        $report = $this->fetchReport();

        $this->assertCount(2, $report);
        $this->assertEquals($this->promoteA->id, $report[0]['id']);
        $this->assertEquals($this->promoteB->id, $report[1]['id']);
    }
}
