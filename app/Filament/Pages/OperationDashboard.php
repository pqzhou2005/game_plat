<?php
namespace App\Filament\Pages;

use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class OperationDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';
    protected static ?string $navigationGroup = '数据中心';
    protected static ?int $navigationSort = 10;
    protected static ?string $navigationLabel = '运营看板';
    protected static ?string $title = '运营数据看板';
    protected static string $view = 'filament.pages.operation-dashboard';

    public string $selectedDate = '';

    // 卡片数据
    public array $cardData = [];

    // 趋势数据
    public array $trendData = [];

    // 排行榜
    public array $rankings = [];

    // 异常提醒
    public array $alerts = [];

    public function mount(): void
    {
        $this->selectedDate = now()->format('Y-m-d');
        $this->loadDashboardData();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            DatePicker::make('selectedDate')
                ->label('日期')
                ->native(false)
                ->displayFormat('Y-m-d')
                ->closeOnDateSelection(),
        ]);
    }

    public function applyFilters(): void
    {
        $this->loadDashboardData();
    }

    public function resetFilters(): void
    {
        $this->selectedDate = now()->format('Y-m-d');
        $this->loadDashboardData();
    }

    protected function loadDashboardData(): void
    {
        $date = Carbon::parse($this->selectedDate)->format('Y-m-d');

        $this->cardData = $this->loadCardData($date);
        $this->trendData = $this->loadTrendData($date);
        $this->rankings = $this->loadRankings($date);
        $this->alerts = $this->loadAlerts($date);
    }

    protected function loadCardData(string $date): array
    {
        return [
            'register' => DB::table('users')->whereDate('created_at', $date)->count(),

            'create_role' => DB::table('role_reports')
                ->where('submit_type', 1)
                ->whereDate('created_at', $date)
                ->distinct('user_id')
                ->count('user_id'),

            'pay_users' => DB::table('payment_orders')
                ->where('status', 'success')
                ->whereDate('paid_at', $date)
                ->distinct('user_id')
                ->count('user_id'),

            'revenue' => (float) DB::table('payment_orders')
                ->where('status', 'success')
                ->whereDate('paid_at', $date)
                ->sum('amount'),

            'orders' => DB::table('payment_orders')
                ->where('status', 'success')
                ->whereDate('paid_at', $date)
                ->count(),

            'deliver_failed' => DB::table('payment_orders')
                ->where('status', 'success')
                ->where('notify_status', 'failed')
                ->whereDate('paid_at', $date)
                ->count(),

            'paid_not_delivered' => DB::table('payment_orders')
                ->where('status', 'success')
                ->where('notify_status', '!=', 'success')
                ->whereDate('paid_at', $date)
                ->count(),

            'promote_register' => DB::table('user_attributions')
                ->whereDate('created_at', $date)
                ->count(),
        ];
    }

    protected function loadTrendData(string $date): array
    {
        $endDate = Carbon::parse($date)->endOfDay();
        $startDate = Carbon::parse($date)->subDays(6)->startOfDay();

        // 注册趋势 — 按日聚合
        $registerTrend = DB::table('users')
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as cnt'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('cnt', 'day')
            ->toArray();

        // 充值趋势 — 按日聚合成功订单金额
        $revenueTrend = DB::table('payment_orders')
            ->select(DB::raw('DATE(paid_at) as day'), DB::raw('COALESCE(SUM(amount), 0) as total'))
            ->where('status', 'success')
            ->whereBetween('paid_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(paid_at)'))
            ->pluck('total', 'day')
            ->toArray();

        // 创角趋势 — 按日聚合去重用户
        $roleTrend = DB::table('role_reports')
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(DISTINCT user_id) as cnt'))
            ->where('submit_type', 1)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('cnt', 'day')
            ->toArray();

        $formatTrend = function (array $source, bool $isAmount) use ($date): array {
            $base = Carbon::parse($date);
            $days = [];
            $maxVal = 0;
            // 生成 7 天的完整序列（从 6 天前到今天）
            for ($i = 6; $i >= 0; $i--) {
                $day = $base->copy()->subDays($i);
                $key = $day->format('Y-m-d');
                $val = (int) ($source[$key] ?? 0);
                $days[] = ['label' => $day->format('m/d'), 'value' => $val];
                if ($val > $maxVal) {
                    $maxVal = $val;
                }
            }
            foreach ($days as &$d) {
                $d['percent'] = $maxVal > 0 ? max(round($d['value'] / $maxVal * 100), 1) : 0;
                $d['display'] = $isAmount ? '¥' . number_format($d['value']) : number_format($d['value']);
            }
            return $days;
        };

        return [
            'register' => $formatTrend($registerTrend, false),
            'revenue'  => $formatTrend($revenueTrend, true),
            'role'     => $formatTrend($roleTrend, false),
        ];
    }

    protected function loadRankings(string $date): array
    {
        // 1. 今日游戏充值排行
        $gameRanking = DB::table('payment_orders')
            ->select('game_id', 'games.name as game_name', DB::raw('COALESCE(SUM(amount), 0) as total'))
            ->join('games', 'payment_orders.game_id', '=', 'games.id')
            ->where('payment_orders.status', 'success')
            ->whereDate('payment_orders.paid_at', $date)
            ->whereNotNull('payment_orders.game_id')
            ->groupBy('payment_orders.game_id', 'games.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->toArray();

        // 2. 今日推广入口注册排行
        $promoteRegisterRanking = DB::table('user_attributions')
            ->select('promote_id', 'promotes.promote_name', DB::raw('COUNT(*) as cnt'))
            ->join('promotes', 'user_attributions.promote_id', '=', 'promotes.id')
            ->whereDate('user_attributions.created_at', $date)
            ->groupBy('user_attributions.promote_id', 'promotes.promote_name')
            ->orderByDesc('cnt')
            ->limit(10)
            ->get()
            ->toArray();

        // 3. 今日推广入口充值排行
        $promoteRevenueRanking = DB::table('payment_orders')
            ->select('promotes.id', 'promotes.promote_name', DB::raw('COALESCE(SUM(payment_orders.amount), 0) as total'))
            ->join('user_attributions', 'payment_orders.user_id', '=', 'user_attributions.user_id')
            ->join('promotes', 'user_attributions.promote_id', '=', 'promotes.id')
            ->where('payment_orders.status', 'success')
            ->whereDate('payment_orders.paid_at', $date)
            ->groupBy('promotes.id', 'promotes.promote_name')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->toArray();

        return [
            'game_revenue'      => $gameRanking,
            'promote_register'  => $promoteRegisterRanking,
            'promote_revenue'   => $promoteRevenueRanking,
        ];
    }

    protected function loadAlerts(string $date): array
    {
        $dateStart = Carbon::parse($date)->startOfDay();
        $dateEnd = Carbon::parse($date)->endOfDay();

        return [
            'deliver_failed' => DB::table('payment_orders')
                ->where('status', 'success')
                ->where('notify_status', 'failed')
                ->whereBetween('paid_at', [$dateStart, $dateEnd])
                ->count(),

            'paid_not_delivered' => DB::table('payment_orders')
                ->where('status', 'success')
                ->where('notify_status', '!=', 'success')
                ->whereBetween('paid_at', [$dateStart, $dateEnd])
                ->count(),

            'long_pending' => DB::table('payment_orders')
                ->where('status', 'pending')
                ->where('created_at', '<', Carbon::parse($date)->subMinutes(10))
                ->whereDate('created_at', $date)
                ->count(),

            'realname_failed' => '-', // 占位，待实名日志表完善后接入
        ];
    }
}
