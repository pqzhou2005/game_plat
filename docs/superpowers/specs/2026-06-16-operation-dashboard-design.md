# 运营数据看板 — 设计文档

> 日期：2026-06-16
> 状态：已批准
> 项目：new602 (Laravel 12 + Filament v3)

## 1. 概述

运营数据看板（Operation Dashboard）是后台首页的核心数据展示页，运营每天一打开后台就能看到今日关键指标。第一版聚焦关键指标展示，不做复杂 BI。

## 2. 导航位置

- **导航分组**：数据中心（新分组）
- **导航标签**：运营看板
- **导航图标**：`heroicon-o-presentation-chart-bar`
- **导航排序**：10
- **URL**：`/admin/operation-dashboard`

## 3. 页面结构

### 3.1 日期筛选

仅支持日期选择，默认当天，可切换任意日期。

- 日期选择器：DatePicker，默认 `now()->format('Y-m-d')`
- 查询按钮：查询
- 重置按钮：重置

后续扩展：游戏筛选、渠道筛选（第一版不做）。

### 3.2 顶部卡片区（8 个指标卡片）

每张卡片包含：图标、指标名、数值、副标题/对比。

| 卡片 | 数据源 | 口径 | 异常颜色 |
|------|--------|------|---------|
| 今日注册 | `users` | `created_at` = 所选日期 | — |
| 今日创角 | `role_reports` | `submit_type=1` AND `created_at` = 所选日期，DISTINCT user_id | — |
| 今日付费人数 | `payment_orders` | `status=success` AND `paid_at` = 所选日期，DISTINCT user_id | — |
| 今日充值金额 | `payment_orders` | `status=success` AND `paid_at` = 所选日期，SUM(amount) | — |
| 今日成功订单 | `payment_orders` | `status=success` AND `paid_at` = 所选日期，COUNT(*) | — |
| 今日发货失败 | `payment_orders` | `status=success` AND `notify_status=failed` AND `paid_at` = 所选日期 | 🔴 红色 |
| 今日支付成功未发货 | `payment_orders` | `status=success` AND `notify_status!=success` AND `paid_at` = 所选日期 | 🟡 黄色 |
| 今日新增推广注册 | `user_attributions` | `created_at` = 所选日期 | — |

### 3.3 趋势区（最近 7 天）

三个并排的简单柱状趋势图，使用纯 CSS 渲染（条形图，高度 = 值/最大值 × 100%）：

1. **最近 7 天注册趋势**
   - 数据：按日聚合 `users.created_at`，近 7 天
   - 图表：7 根竖条 + 顶部数值 + 底部日期标签

2. **最近 7 天充值趋势**
   - 数据：按日聚合 `payment_orders.paid_at`（status=success），SUM(amount)，近 7 天
   - 图表：7 根竖条 + 顶部金额 + 底部日期标签

3. **最近 7 天创角趋势**
   - 数据：按日聚合 `role_reports.created_at`（submit_type=1），DISTINCT user_id，近 7 天
   - 图表：7 根竖条 + 顶部数值 + 底部日期标签

### 3.4 排行榜

三个排行榜表格，各取 Top 10：

1. **今日游戏充值排行**
   - `payment_orders` 按 `game_id` JOIN `games` GROUP BY
   - 条件：`status=success` AND `paid_at` = 所选日期
   - 列：排名、游戏名、充值金额

2. **今日推广入口注册排行**
   - `user_attributions` 按 `promote_id` GROUP BY
   - 条件：`created_at` = 所选日期
   - 列：排名、推广入口名、注册人数

3. **今日推广入口充值排行**
   - `payment_orders` JOIN `user_attributions` ON `user_id`
   - 条件：`status=success` AND `paid_at` = 所选日期，按 promote_id GROUP BY
   - 列：排名、推广入口名、充值金额

### 3.5 异常提醒区

异常条目带链接跳转到订单对账页：

| 异常项 | 口径 | 跳转链接 |
|--------|------|---------|
| 发货失败订单数 | `status=success` AND `notify_status=failed` AND `paid_at` = 所选日期 | `/admin/order-reconciliation` |
| 支付成功未发货订单数 | `status=success` AND `notify_status!=success` AND `paid_at` = 所选日期 | `/admin/order-reconciliation` |
| 长时间 pending 订单数 | `status=pending` AND `created_at` < (所选日期 10 分钟前) | `/admin/order-reconciliation` |
| 今日实名失败数（占位） | 第一版显示为 `-`，待实名日志表完善后接入 | — |

## 4. 技术方案

### 4.1 文件结构

```
app/Filament/Pages/OperationDashboard.php      # 页面类
resources/views/filament/pages/operation-dashboard.blade.php  # 视图
```

### 4.2 页面类设计

继承 `Filament\Pages\Page`，使用自定义 Blade 视图（模式与 `PromotePerformance`、`UserInvestigation` 一致）。

**属性**：
- `$selectedDate`：日期（默认今天）
- `$cardData`：数组，8 个卡片数据
- `$trendData`：数组，3 组 7 天趋势数据
- `$rankings`：数组，3 个排行榜
- `$alerts`：数组，异常提醒数据

**方法**：
- `mount()`：设置默认日期，加载数据
- `form()`：日期选择器表单
- `applyFilters()`：查询按钮
- `loadDashboardData()`：核心方法，加载所有数据

### 4.3 SQL 策略

所有聚合在一个请求周期内完成，不做 AJAX 懒加载。

- 卡片区：8 个独立 SQL COUNT/SUM 查询（简单直接，每次查询都是单表聚合，性能良好）
- 趋势区：3 个按日 GROUP BY 查询
- 排行榜：3 个 GROUP BY + JOIN + LIMIT 10 查询
- 异常区：3 个 COUNT 查询

总计约 14 个轻量查询。如果后续需要优化，可合并为更少的子查询 JOIN，但第一版以简单可读为优先。

### 4.4 趋势图实现

纯 CSS 条形图，无需 JS 图表库：

```blade
{{-- 每根柱子高度 = 值 ÷ 7天最大值 × 100% --}}
<div class="flex items-end gap-2 h-32">
    @foreach ($trendData['register'] as $day)
        <div class="flex flex-col items-center flex-1">
            <span class="text-xs text-gray-600 mb-1">{{ $day['value'] }}</span>
            <div class="w-full bg-primary-200 rounded-t"
                 style="height: {{ $day['percent'] }}%"></div>
            <span class="text-xs text-gray-400 mt-1">{{ $day['label'] }}</span>
        </div>
    @endforeach
</div>
```

### 4.5 页眉/标题

页面标题："运营数据看板"，副标题显示所选日期范围。

## 5. 数据口径详细说明

| 指标 | 表 | WHERE | SELECT |
|------|------|------|------|
| 今日注册 | `users` | `DATE(created_at) = :date` | `COUNT(*)` |
| 今日创角 | `role_reports` | `submit_type=1 AND DATE(created_at) = :date` | `COUNT(DISTINCT user_id)` |
| 今日付费人数 | `payment_orders` | `status='success' AND DATE(paid_at) = :date` | `COUNT(DISTINCT user_id)` |
| 今日充值金额 | `payment_orders` | `status='success' AND DATE(paid_at) = :date` | `COALESCE(SUM(amount), 0)` |
| 今日成功订单 | `payment_orders` | `status='success' AND DATE(paid_at) = :date` | `COUNT(*)` |
| 今日发货失败 | `payment_orders` | `status='success' AND notify_status='failed' AND DATE(paid_at) = :date` | `COUNT(*)` |
| 今日支付成功未发货 | `payment_orders` | `status='success' AND notify_status!='success' AND DATE(paid_at) = :date` | `COUNT(*)` |
| 今日新增推广注册 | `user_attributions` | `DATE(created_at) = :date` | `COUNT(*)` |

## 6. 验收标准

- [x] 打开页面默认显示今日数据
- [x] 今日注册、创角、充值、订单数准确
- [x] 能看到最近 7 天注册和充值趋势
- [x] 能看到游戏充值排行
- [x] 能看到推广入口注册/充值排行
- [x] 能看到发货异常提醒
- [x] 日期切换后数据刷新
- [x] 页面只读，不修改业务数据

## 7. 不做的功能

- 不引入 Chart.js 或其他 JS 图表库
- 不做游戏筛选（后续版本）
- 不做渠道筛选（后续版本）
- 不做 CSV 导出（后续版本）
- 不做明细钻取（后续版本）
- 不修改任何业务数据

## 8. 后续扩展

1. 游戏筛选 + 渠道筛选
2. 图表交互升级（Chart.js 折线图）
3. CSV 导出 / 自动推送日报
4. 自定义卡片布局（运营可配置首页展示哪些卡片）
5. 实时数据（WebSocket 推送）
