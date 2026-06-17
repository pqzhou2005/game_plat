{{--
    resources/views/filament/pages/operation-dashboard.blade.php
    运营数据看板 — 顶部卡片 + 趋势图 + 排行榜 + 异常提醒
--}}
<x-filament::page>
    {{-- 筛选栏 --}}
    <div class="flex gap-4 items-end flex-wrap">
        <div class="w-48">
            {{ $this->form->getComponent('selectedDate') }}
        </div>
        <div class="flex gap-2">
            <x-filament::button wire:click="applyFilters" color="primary" size="sm">
                查询
            </x-filament::button>
            <x-filament::button wire:click="resetFilters" color="gray" size="sm">
                重置
            </x-filament::button>
        </div>
        <div class="text-sm text-gray-400 ml-auto">
            查询日期：{{ $selectedDate }}
        </div>
    </div>

    {{-- ========== 顶部指标卡片 ========== --}}
    <div class="grid grid-cols-4 gap-4">
        {{-- 今日注册 --}}
        <x-dashboard-card
            icon="user-plus"
            title="今日注册"
            :value="number_format($cardData['register'])"
            color="primary"
        />

        {{-- 今日创角 --}}
        <x-dashboard-card
            icon="sparkles"
            title="今日创角"
            :value="number_format($cardData['create_role'])"
            color="success"
        />

        {{-- 今日付费人数 --}}
        <x-dashboard-card
            icon="credit-card"
            title="今日付费人数"
            :value="number_format($cardData['pay_users'])"
            color="warning"
        />

        {{-- 今日充值金额 --}}
        <x-dashboard-card
            icon="banknotes"
            title="今日充值金额"
            :value="'¥' . number_format($cardData['revenue'], 2)"
            color="danger"
        />

        {{-- 今日成功订单 --}}
        <x-dashboard-card
            icon="shopping-cart"
            title="今日成功订单"
            :value="number_format($cardData['orders'])"
            color="info"
        />

        {{-- 今日发货失败 --}}
        <x-dashboard-card
            icon="exclamation-triangle"
            title="今日发货失败"
            :value="number_format($cardData['deliver_failed'])"
            color="danger"
            :alert="$cardData['deliver_failed'] > 0"
        />

        {{-- 今日支付成功未发货 --}}
        <x-dashboard-card
            icon="clock"
            title="今日支付成功未发货"
            :value="number_format($cardData['paid_not_delivered'])"
            color="warning"
            :alert="$cardData['paid_not_delivered'] > 0"
        />

        {{-- 今日新增推广注册 --}}
        <x-dashboard-card
            icon="megaphone"
            title="今日新增推广注册"
            :value="number_format($cardData['promote_register'])"
            color="gray"
        />
    </div>

    {{-- ========== 趋势区 ========== --}}
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-chart-bar class="w-5 h-5 text-primary-500" />
                最近 7 天趋势
            </div>
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            {{-- 注册趋势 --}}
            <div>
                <h4 class="text-sm font-medium text-gray-600 mb-3">注册趋势</h4>
                <div class="flex items-end gap-1.5 h-36">
                    @foreach ($trendData['register'] as $bar)
                        <div class="flex flex-col items-center flex-1 h-full justify-end">
                            <span class="text-xs text-gray-600 font-medium mb-1 tabular-nums">{{ $bar['display'] }}</span>
                            <div class="w-full bg-primary-400 hover:bg-primary-500 rounded-t transition-colors"
                                 style="height: {{ $bar['percent'] }}%"></div>
                            <span class="text-xs text-gray-400 mt-1.5">{{ $bar['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- 充值趋势 --}}
            <div>
                <h4 class="text-sm font-medium text-gray-600 mb-3">充值趋势</h4>
                <div class="flex items-end gap-1.5 h-36">
                    @foreach ($trendData['revenue'] as $bar)
                        <div class="flex flex-col items-center flex-1 h-full justify-end">
                            <span class="text-xs text-gray-600 font-medium mb-1 tabular-nums">{{ $bar['display'] }}</span>
                            <div class="w-full bg-success-400 hover:bg-success-500 rounded-t transition-colors"
                                 style="height: {{ $bar['percent'] }}%"></div>
                            <span class="text-xs text-gray-400 mt-1.5">{{ $bar['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- 创角趋势 --}}
            <div>
                <h4 class="text-sm font-medium text-gray-600 mb-3">创角趋势</h4>
                <div class="flex items-end gap-1.5 h-36">
                    @foreach ($trendData['role'] as $bar)
                        <div class="flex flex-col items-center flex-1 h-full justify-end">
                            <span class="text-xs text-gray-600 font-medium mb-1 tabular-nums">{{ $bar['display'] }}</span>
                            <div class="w-full bg-warning-400 hover:bg-warning-500 rounded-t transition-colors"
                                 style="height: {{ $bar['percent'] }}%"></div>
                            <span class="text-xs text-gray-400 mt-1.5">{{ $bar['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </x-filament::section>

    {{-- ========== 排行榜 ========== --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- 今日游戏充值排行 --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-trophy class="w-5 h-5 text-warning-500" />
                    今日游戏充值排行
                </div>
            </x-slot>

            @if (count($rankings['game_revenue']) > 0)
                <div class="space-y-1">
                    @foreach ($rankings['game_revenue'] as $i => $row)
                        <div class="flex items-center justify-between py-1.5 px-2 rounded {{ $i < 3 ? 'bg-warning-50' : 'hover:bg-gray-50' }}">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold {{ $i < 3 ? 'text-warning-600' : 'text-gray-400' }} w-5 text-center">
                                    {{ $i + 1 }}
                                </span>
                                <span class="text-sm text-gray-700 truncate">{{ $row->game_name }}</span>
                            </div>
                            <span class="text-sm font-medium text-gray-700 tabular-nums">¥{{ number_format($row->total) }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-6 text-center text-gray-400 text-sm">暂无数据</div>
            @endif
        </x-filament::section>

        {{-- 今日推广入口注册排行 --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-user-group class="w-5 h-5 text-primary-500" />
                    今日推广入口注册排行
                </div>
            </x-slot>

            @if (count($rankings['promote_register']) > 0)
                <div class="space-y-1">
                    @foreach ($rankings['promote_register'] as $i => $row)
                        <div class="flex items-center justify-between py-1.5 px-2 rounded {{ $i < 3 ? 'bg-primary-50' : 'hover:bg-gray-50' }}">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold {{ $i < 3 ? 'text-primary-600' : 'text-gray-400' }} w-5 text-center">
                                    {{ $i + 1 }}
                                </span>
                                <span class="text-sm text-gray-700 truncate">{{ $row->promote_name }}</span>
                            </div>
                            <span class="text-sm font-medium text-gray-700 tabular-nums">{{ number_format($row->cnt) }} 人</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-6 text-center text-gray-400 text-sm">暂无数据</div>
            @endif
        </x-filament::section>

        {{-- 今日推广入口充值排行 --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-currency-yen class="w-5 h-5 text-success-500" />
                    今日推广入口充值排行
                </div>
            </x-slot>

            @if (count($rankings['promote_revenue']) > 0)
                <div class="space-y-1">
                    @foreach ($rankings['promote_revenue'] as $i => $row)
                        <div class="flex items-center justify-between py-1.5 px-2 rounded {{ $i < 3 ? 'bg-success-50' : 'hover:bg-gray-50' }}">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-bold {{ $i < 3 ? 'text-success-600' : 'text-gray-400' }} w-5 text-center">
                                    {{ $i + 1 }}
                                </span>
                                <span class="text-sm text-gray-700 truncate">{{ $row->promote_name }}</span>
                            </div>
                            <span class="text-sm font-medium text-gray-700 tabular-nums">¥{{ number_format($row->total) }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="py-6 text-center text-gray-400 text-sm">暂无数据</div>
            @endif
        </x-filament::section>
    </div>

    {{-- ========== 异常提醒 ========== --}}
    @if (array_sum(array_map(fn($v) => is_numeric($v) ? $v : 0, $alerts)) > 0 || $alerts['realname_failed'] !== '-')
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-shield-exclamation class="w-5 h-5 text-danger-500" />
                异常提醒
            </div>
        </x-slot>

        <div class="space-y-2">
            @if (($alerts['deliver_failed'] ?? 0) > 0)
                <div class="flex items-center justify-between py-2 px-3 bg-danger-50 rounded-lg">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-x-circle class="w-5 h-5 text-danger-500 shrink-0" />
                        <span class="text-sm text-gray-700">发货失败订单</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-bold text-danger-600">{{ $alerts['deliver_failed'] }} 笔</span>
                        <a href="/admin/order-reconciliation"
                           class="text-xs text-primary-600 hover:text-primary-800 underline">
                            去订单对账处理
                        </a>
                    </div>
                </div>
            @endif

            @if (($alerts['paid_not_delivered'] ?? 0) > 0)
                <div class="flex items-center justify-between py-2 px-3 bg-warning-50 rounded-lg">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-clock class="w-5 h-5 text-warning-600 shrink-0" />
                        <span class="text-sm text-gray-700">支付成功未发货</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-bold text-warning-600">{{ $alerts['paid_not_delivered'] }} 笔</span>
                        <a href="/admin/order-reconciliation"
                           class="text-xs text-primary-600 hover:text-primary-800 underline">
                            去订单对账处理
                        </a>
                    </div>
                </div>
            @endif

            @if (($alerts['long_pending'] ?? 0) > 0)
                <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-clock class="w-5 h-5 text-gray-500 shrink-0" />
                        <span class="text-sm text-gray-700">长时间 pending 订单（超过 10 分钟未支付）</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-bold text-gray-600">{{ $alerts['long_pending'] }} 笔</span>
                        <a href="/admin/order-reconciliation"
                           class="text-xs text-primary-600 hover:text-primary-800 underline">
                            去订单对账处理
                        </a>
                    </div>
                </div>
            @endif

            @if ($alerts['realname_failed'] !== '-' && ($alerts['realname_failed'] ?? 0) > 0)
                <div class="flex items-center justify-between py-2 px-3 bg-orange-50 rounded-lg">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-identification class="w-5 h-5 text-orange-500 shrink-0" />
                        <span class="text-sm text-gray-700">今日实名认证失败</span>
                    </div>
                    <span class="text-sm font-bold text-orange-600">{{ $alerts['realname_failed'] }} 次</span>
                </div>
            @endif
        </div>
    </x-filament::section>
    @endif

    {{-- 数据日期指示 --}}
    <div class="text-xs text-gray-400 text-center pt-2">
        数据更新于 {{ now()->format('Y-m-d H:i:s') }} · 页面只读，不修改业务数据
    </div>
</x-filament::page>
