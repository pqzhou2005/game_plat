# 推广效果报表 (Promote Performance Report)

**日期**: 2026-06-16
**状态**: Approved

## 概述

推广效果报表将「推广入口 → 注册 → 创角 → 付费」链路串联，让运营可以直接回答：
- 哪个落地页带来多少注册？
- 注册后有没有创角？
- 有没有付费？
- 哪个推广入口质量好？

## 功能入口

- **后台菜单**: 推广管理 → 推广效果
- **导航排序**: `PromoteResource::$navigationSort = 10`, `PromotePerformance::$navigationSort = 20`

## 文件结构

```
app/Filament/Pages/PromotePerformance.php         # 页面逻辑
resources/views/filament/pages/promote-performance.blade.php  # Blade 视图
```

## 数据模型

涉及表:
- `promotes` — 推广入口（主表）
- `user_attributions` — 用户归因记录
- `role_reports` — 角色上报（`submit_type = 1` 为创角）
- `payment_orders` — 支付订单（`status = 'success'` 为成功支付）
- `games` — 游戏（关联查询游戏名称）

## 统计口径

- **设计**: 按注册归因时间筛选的 cohort（队列）口径
- **默认时间**: 最近 30 天（`today - 29 days` 到 `today`）
- **口径说明**: "按注册归因时间筛选，展示该批注册用户截至当前的后续创角与付费表现"
- **时间筛选字段**: `user_attributions.created_at`
- **边界处理**: `whereBetween('ua.created_at', [startOfDay, endOfDay])`

## 指标定义

| 指标 | 口径 | 数据来源 |
|------|------|---------|
| 注册人数 | 按 promote_id COUNT(DISTINCT user_id) | user_attributions |
| 创角用户数 | submit_type=1，按 user_id 去重 | role_reports |
| 创角角色数 | 按 game_id+server_id+role_id 去重 | role_reports |
| 付费人数 | status=success，按 user_id 去重 | payment_orders |
| 订单数 | status=success，COUNT(*) | payment_orders |
| 充值金额 | status=success，SUM(amount) | payment_orders |
| 注册转创角率 | 创角用户数 / 注册人数 × 100% | 计算字段 |
| 注册转付费率 | 付费人数 / 注册人数 × 100% | 计算字段 |
| ARPPU | 充值金额 / 付费人数 | 计算字段 |

**空值规则**: 注册人数=0 时转化率显示 `-`; 付费人数=0 时 ARPPU 显示 `-`

## SQL 方案

采用 **CTE + 子查询** 方案，避免 `role_reports × payment_orders` 交叉放大：

1. 子查询 `attributed_users`: 筛选时间范围内的归因用户
2. 子查询 `role_stats`: 按 user_id 聚合创角数据（仅 submit_type=1）
3. 子查询 `payment_stats`: 按 user_id 聚合付费数据（仅 status=success）
4. 主查询: `promotes` LEFT JOIN 上述三个子查询，按 promote_id GROUP BY
5. 以 `promotes` 为主表确保无注册入口显示为 0

时间筛选条件放在 `attributed_users` 子查询的 WHERE 中，不在外层 WHERE 过滤，避免无注册入口被排除。

## 筛选条件

第一版支持的筛选:
- **注册时间范围**: DatePicker 起始/截止（默认近 30 天）
- **推广入口**: Select（可搜索），筛选 promote_id
- **游戏**: Select（可搜索），筛选 promotes.game_id

游戏筛选只筛 `promotes.game_id`，不筛子查询中的记录。

## 表格与排序

**列定义**:
推广入口 | 推广码 | 游戏 | 注册人数 | 创角用户 | 创角角色 | 付费人数 | 成功订单 | 充值金额 | 注册→创角 | 注册→付费 | ARPPU

**可排序列**: 注册人数、付费人数、充值金额
**默认排序**: 注册人数 DESC
**分页**: 第一版不做服务端分页，全部查询后在 Collection 排序

## 数据导出

第一版不做导出。代码结构预留 `loadReportData()` 公共方法，后续导出直接复用。第二版可加 CSV 导出。

## 实现约束

- 使用 Filament Page（自定义视图），不使用 `InteractsWithTable`
- 页面 mount 时自动加载数据
- 筛选变化后重新查询
- 使用 `DB::raw()` + `joinSub()` 等方式，避免手写完整 SQL 字符串
- 使用 Carbon 处理日期边界
