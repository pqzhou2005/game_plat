<?php
namespace App\Filament\Pages;

use App\Models\Game;
use App\Models\Promote;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class PromotePerformance extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = '推广管理';
    protected static ?int $navigationSort = 20;
    protected static ?string $navigationLabel = '推广效果';
    protected static ?string $title = '推广效果报表';
    protected static string $view = 'filament.pages.promote-performance';

    // 筛选条件
    public string $startDate = '';
    public string $endDate = '';
    public ?int $gameId = null;
    public ?int $promoteId = null;

    // 数据
    public array $reportData = [];
    public string $sortField = 'register_count';
    public string $sortDirection = 'desc';

    // 口径说明
    public string $cohortDescription = '统计口径：按注册归因时间筛选，展示该批注册用户截至当前的后续创角与付费表现。';

    public function mount(): void
    {
        $this->startDate = now()->subDays(29)->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->loadReportData();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            DatePicker::make('startDate')
                ->label('注册起始')
                ->native(false)
                ->displayFormat('Y-m-d')
                ->closeOnDateSelection(),
            DatePicker::make('endDate')
                ->label('注册截止')
                ->native(false)
                ->displayFormat('Y-m-d')
                ->closeOnDateSelection(),
            Select::make('gameId')
                ->label('游戏')
                ->placeholder('全部游戏')
                ->options(Game::pluck('name', 'id'))
                ->searchable()
                ->nullable(),
            Select::make('promoteId')
                ->label('推广入口')
                ->placeholder('全部入口')
                ->options(Promote::pluck('promote_name', 'id'))
                ->searchable()
                ->nullable(),
        ]);
    }

    public function applyFilters(): void
    {
        $this->loadReportData();
    }

    public function resetFilters(): void
    {
        $this->startDate = now()->subDays(29)->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->gameId = null;
        $this->promoteId = null;
        $this->sortField = 'register_count';
        $this->sortDirection = 'desc';
        $this->loadReportData();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'desc' ? 'asc' : 'desc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
        $this->sortReportData();
    }

    public function sortIndicator(string $field): string
    {
        if ($this->sortField !== $field) {
            return '';
        }
        return $this->sortDirection === 'desc' ? ' ↓' : ' ↑';
    }

    public function loadReportData(): void
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        // 子查询1: 归因用户
        $attributedUsers = DB::table('user_attributions')
            ->select('user_id', 'promote_id')
            ->whereBetween('created_at', [$start, $end]);

        // 子查询2: 创角统计（按 user_id 聚合）
        $roleStats = DB::table('role_reports')
            ->select('user_id',
                DB::raw('COUNT(DISTINCT CONCAT(game_id, "-", server_id, "-", role_id)) as role_count')
            )
            ->where('submit_type', 1)
            ->groupBy('user_id');

        // 子查询3: 付费统计（按 user_id 聚合）
        $paymentStats = DB::table('payment_orders')
            ->select('user_id',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('COALESCE(SUM(amount), 0) as revenue')
            )
            ->where('status', 'success')
            ->groupBy('user_id');

        // 主查询
        $query = DB::table('promotes as p')
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
            ->when($this->gameId, fn($q) => $q->where('p.game_id', $this->gameId))
            ->when($this->promoteId, fn($q) => $q->where('p.id', $this->promoteId))
            ->groupBy('p.id', 'p.promote_code', 'p.promote_name', 'p.game_id', 'g.name');

        $results = $query->get();

        // 计算转化率字段
        $this->reportData = $results->map(function ($row) {
            $registerCount = (int) $row->register_count;
            $payUserCount = (int) $row->pay_user_count;

            return [
                'id' => $row->id,
                'promote_code' => $row->promote_code,
                'promote_name' => $row->promote_name,
                'game_name' => $row->game_name ?? '-',
                'register_count' => $registerCount,
                'role_user_count' => (int) $row->role_user_count,
                'role_count' => (int) $row->role_count,
                'pay_user_count' => $payUserCount,
                'order_count' => (int) $row->order_count,
                'revenue' => (float) $row->revenue,
                'register_to_role_rate' => $registerCount > 0
                    ? round($row->role_user_count / $registerCount * 100, 2) . '%'
                    : '-',
                'register_to_pay_rate' => $registerCount > 0
                    ? round($payUserCount / $registerCount * 100, 2) . '%'
                    : '-',
                'arppu' => $payUserCount > 0
                    ? round($row->revenue / $payUserCount, 2)
                    : '-',
            ];
        })->toArray();

        $this->sortReportData();
    }

    protected function sortReportData(): void
    {
        if (empty($this->reportData)) {
            return;
        }

        $field = $this->sortField;
        $direction = $this->sortDirection;

        usort($this->reportData, function ($a, $b) use ($field, $direction) {
            $valA = $a[$field] ?? 0;
            $valB = $b[$field] ?? 0;

            if ($valA === '-') $valA = -1;
            if ($valB === '-') $valB = -1;

            $valA = is_numeric($valA) ? (float) $valA : -1;
            $valB = is_numeric($valB) ? (float) $valB : -1;

            return $direction === 'desc' ? $valB <=> $valA : $valA <=> $valB;
        });
    }
}
