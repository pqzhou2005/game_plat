# 推广效果报表 (PromotePerformance) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a Filament Page that aggregates promote performance data across user_attributions, role_reports, and payment_orders, showing register→role→pay conversion for each promote.

**Architecture:** One Filament Page (custom view, no InteractsWithTable) that uses Laravel query builder with joinSub for cross-table aggregation. All data loaded into a Collection, sorted client-side. Subqueries prevent cross-join amplification.

**Tech Stack:** Laravel 11, Filament 3, MySQL, PHP 8.2

---

### Task 1: Create PromotePerformance Page Class

**Files:**
- Create: `app/Filament/Pages/PromotePerformance.php`

- [ ] **Step 1: Create the page class with navigation config, filter properties, and mount method**

```php
<?php
namespace App\Filament\Pages;

use App\Models\Game;
use App\Models\Promote;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PromotePerformance extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = '推广管理';
    protected static ?string $navigationSort = 20;
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
        // 默认最近30天
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

        // 数值型字段排序（处理 '-' 值）
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
```

- [ ] **Step 2: Add sort arrow indicator helpers (add to the page class)**

Add this method to the `PromotePerformance` class:

```php
    public function sortIndicator(string $field): string
    {
        if ($this->sortField !== $field) {
            return '';
        }
        return $this->sortDirection === 'desc' ? ' ↓' : ' ↑';
    }
```

- [ ] **Step 3: Commit**

```bash
git add app/Filament/Pages/PromotePerformance.php
git commit -m "feat: add PromotePerformance page class with subquery aggregation"
```

---

### Task 2: Create Blade View

**Files:**
- Create: `resources/views/filament/pages/promote-performance.blade.php`

- [ ] **Step 1: Create the view file**

```blade
{{-- resources/views/filament/pages/promote-performance.blade.php --}}
<x-filament::page>
    {{-- 筛选条件 --}}
    <div class="space-y-4">
        <div class="flex gap-4 items-end flex-wrap">
            <div class="w-44">
                {{ $this->form->getComponent('startDate') }}
            </div>
            <div class="w-44">
                {{ $this->form->getComponent('endDate') }}
            </div>
            <div class="w-56">
                {{ $this->form->getComponent('gameId') }}
            </div>
            <div class="w-56">
                {{ $this->form->getComponent('promoteId') }}
            </div>
            <div class="flex gap-2">
                <x-filament::button wire:click="applyFilters" color="primary" size="sm">
                    查询
                </x-filament::button>
                <x-filament::button wire:click="resetFilters" color="gray" size="sm">
                    重置
                </x-filament::button>
            </div>
        </div>

        {{-- 口径说明 --}}
        <div class="bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 text-sm text-gray-600">
            <div class="flex items-center gap-2">
                <x-heroicon-o-information-circle class="w-4 h-4 text-gray-400 shrink-0" />
                <span>{{ $cohortDescription }}</span>
            </div>
            <div class="mt-1 text-xs text-gray-400">
                查询时间：{{ $startDate }} ~ {{ $endDate }}
            </div>
        </div>

        {{-- 汇总统计 --}}
        @php
            $totalRegister = collect($reportData)->sum('register_count');
            $totalRoleUser = collect($reportData)->sum('role_user_count');
            $totalRole = collect($reportData)->sum('role_count');
            $totalPayUser = collect($reportData)->sum('pay_user_count');
            $totalOrder = collect($reportData)->sum('order_count');
            $totalRevenue = collect($reportData)->sum('revenue');
            $totalRoleRate = $totalRegister > 0
                ? round($totalRoleUser / $totalRegister * 100, 2) . '%'
                : '-';
            $totalPayRate = $totalRegister > 0
                ? round($totalPayUser / $totalRegister * 100, 2) . '%'
                : '-';
            $totalArppu = $totalPayUser > 0
                ? round($totalRevenue / $totalPayUser, 2)
                : '-';
        @endphp

        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-table-cells class="w-5 h-5 text-primary-500" />
                    推广效果数据
                </div>
            </x-slot>

            @if (count($reportData) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm whitespace-nowrap">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-2 px-3 text-gray-500 font-medium">推广入口</th>
                                <th class="text-left py-2 px-3 text-gray-500 font-medium">推广码</th>
                                <th class="text-left py-2 px-3 text-gray-500 font-medium">游戏</th>
                                <th class="text-right py-2 px-3 text-gray-500 font-medium cursor-pointer hover:text-primary-600"
                                    wire:click="sortBy('register_count')">
                                    注册人数{{ $sortIndicator('register_count') }}
                                </th>
                                <th class="text-right py-2 px-3 text-gray-500 font-medium">创角用户</th>
                                <th class="text-right py-2 px-3 text-gray-500 font-medium">创角角色</th>
                                <th class="text-right py-2 px-3 text-gray-500 font-medium cursor-pointer hover:text-primary-600"
                                    wire:click="sortBy('pay_user_count')">
                                    付费人数{{ $sortIndicator('pay_user_count') }}
                                </th>
                                <th class="text-right py-2 px-3 text-gray-500 font-medium cursor-pointer hover:text-primary-600"
                                    wire:click="sortBy('order_count')">
                                    成功订单{{ $sortIndicator('order_count') }}
                                </th>
                                <th class="text-right py-2 px-3 text-gray-500 font-medium cursor-pointer hover:text-primary-600"
                                    wire:click="sortBy('revenue')">
                                    充值金额{{ $sortIndicator('revenue') }}
                                </th>
                                <th class="text-right py-2 px-3 text-gray-500 font-medium">注册→创角</th>
                                <th class="text-right py-2 px-3 text-gray-500 font-medium">注册→付费</th>
                                <th class="text-right py-2 px-3 text-gray-500 font-medium">ARPPU</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reportData as $row)
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-2 px-3 text-gray-700 font-medium">{{ $row['promote_name'] }}</td>
                                <td class="py-2 px-3 text-gray-500 font-mono text-xs">{{ $row['promote_code'] }}</td>
                                <td class="py-2 px-3 text-gray-700">{{ $row['game_name'] }}</td>
                                <td class="py-2 px-3 text-right text-gray-700 tabular-nums">{{ number_format($row['register_count']) }}</td>
                                <td class="py-2 px-3 text-right text-gray-700 tabular-nums">{{ number_format($row['role_user_count']) }}</td>
                                <td class="py-2 px-3 text-right text-gray-700 tabular-nums">{{ number_format($row['role_count']) }}</td>
                                <td class="py-2 px-3 text-right text-gray-700 tabular-nums">{{ number_format($row['pay_user_count']) }}</td>
                                <td class="py-2 px-3 text-right text-gray-700 tabular-nums">{{ number_format($row['order_count']) }}</td>
                                <td class="py-2 px-3 text-right text-gray-700 tabular-nums">{{ number_format($row['revenue'], 2) }}</td>
                                <td class="py-2 px-3 text-right tabular-nums
                                    {{ $row['register_to_role_rate'] === '-' ? 'text-gray-400' : 'text-gray-700' }}">
                                    {{ $row['register_to_role_rate'] }}
                                </td>
                                <td class="py-2 px-3 text-right tabular-nums
                                    {{ $row['register_to_pay_rate'] === '-' ? 'text-gray-400' : 'text-gray-700' }}">
                                    {{ $row['register_to_pay_rate'] }}
                                </td>
                                <td class="py-2 px-3 text-right tabular-nums
                                    {{ $row['arppu'] === '-' ? 'text-gray-400' : 'text-gray-700' }}">
                                    {{ $row['arppu'] === '-' ? '-' : number_format((float) $row['arppu'], 2) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        {{-- 汇总行 --}}
                        <tfoot>
                            <tr class="border-t-2 border-gray-300 bg-gray-50 font-medium">
                                <td class="py-2 px-3 text-gray-700">合计</td>
                                <td class="py-2 px-3"></td>
                                <td class="py-2 px-3"></td>
                                <td class="py-2 px-3 text-right text-gray-800 tabular-nums">{{ number_format($totalRegister) }}</td>
                                <td class="py-2 px-3 text-right text-gray-800 tabular-nums">{{ number_format($totalRoleUser) }}</td>
                                <td class="py-2 px-3 text-right text-gray-800 tabular-nums">{{ number_format($totalRole) }}</td>
                                <td class="py-2 px-3 text-right text-gray-800 tabular-nums">{{ number_format($totalPayUser) }}</td>
                                <td class="py-2 px-3 text-right text-gray-800 tabular-nums">{{ number_format($totalOrder) }}</td>
                                <td class="py-2 px-3 text-right text-gray-800 tabular-nums">{{ number_format($totalRevenue, 2) }}</td>
                                <td class="py-2 px-3 text-right text-gray-800 tabular-nums">{{ $totalRoleRate }}</td>
                                <td class="py-2 px-3 text-right text-gray-800 tabular-nums">{{ $totalPayRate }}</td>
                                <td class="py-2 px-3 text-right text-gray-800 tabular-nums">{{ $totalArppu === '-' ? '-' : number_format((float) $totalArppu, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="mt-2 text-xs text-gray-400">
                    共 {{ count($reportData) }} 条记录
                </div>
            @else
                <div class="py-8 text-center text-gray-400">
                    暂无数据
                </div>
            @endif
        </x-filament::section>
    </div>
</x-filament::page>
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/filament/pages/promote-performance.blade.php
git commit -m "feat: add promote performance blade view with sortable table"
```

---

### Task 3: Set PromoteResource navigation sort

**Files:**
- Modify: `app/Filament/Resources/PromoteResource.php:14` (after the existing properties, add navigationSort)

- [ ] **Step 1: Add navigationSort to PromoteResource**

```php
    protected static ?string $navigationSort = 10;
```

Add this line after `protected static ?string $pluralModelLabel = '推广入口';` (line 20).

- [ ] **Step 2: Commit**

```bash
git add app/Filament/Resources/PromoteResource.php
git commit -m "chore: set PromoteResource navigation sort to 10"
```

---

### Self-Review Checklist

1. **Spec coverage:**
   - ✅ Navigation: "推广管理 → 推广效果" with sort=20 — Task 1 + Task 3
   - ✅ Default time range: last 30 days — Task 1 `mount()` sets defaults
   - ✅ Cohort style: date filter on `ua.created_at` only in subquery — Task 1 `loadReportData()`
   - ✅ Promotes as main table: LEFT JOIN with date filter in subquery — Task 1
   - ✅ Metrics: register_count, role_user_count, role_count, pay_user_count, order_count, revenue — Task 1
   - ✅ Conversion rates: register_to_role_rate, register_to_pay_rate — Task 1 map
   - ✅ ARPPU — Task 1 map
   - ✅ Zero-entry handling: null coalesce, '-' for division by zero — Task 1 map + Task 2 view
   - ✅ Sort: register_count, pay_user_count, revenue with direction toggle — Task 1 `sortBy()` + Task 2 view
   - ✅ Default sort: register_count DESC — Task 1 `$sortField` defaults
   - ✅ No export in v1 — not implemented
   - ✅ Filter: game, promote — Task 1 `form()`

2. **Placeholder scan:** No TBD, TODO, or vague steps — all code complete.

3. **Type consistency:** 
   - `$sortField` → string, `$sortDirection` → string — consistent across `mount()`, `sortBy()`, `loadReportData()`
   - `reportData` array has consistent keys — used the same in both PHP and Blade

---

**Plan complete and saved to `docs/superpowers/plans/2026-06-16-promote-performance.md`.**

Two execution options:

**1. Subagent-Driven (recommended)** — I dispatch a fresh subagent per task, review between tasks, fast iteration

**2. Inline Execution** — Execute tasks in this session using executing-plans, batch execution with checkpoints

**Which approach?**
