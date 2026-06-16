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
                                <td class="py-2 px-3 text-right text-gray-800 tabular-nums">{{ number_format($totals['register_count']) }}</td>
                                <td class="py-2 px-3 text-right text-gray-800 tabular-nums">{{ number_format($totals['role_user_count']) }}</td>
                                <td class="py-2 px-3 text-right text-gray-800 tabular-nums">{{ number_format($totals['role_count']) }}</td>
                                <td class="py-2 px-3 text-right text-gray-800 tabular-nums">{{ number_format($totals['pay_user_count']) }}</td>
                                <td class="py-2 px-3 text-right text-gray-800 tabular-nums">{{ number_format($totals['order_count']) }}</td>
                                <td class="py-2 px-3 text-right text-gray-800 tabular-nums">{{ number_format($totals['revenue'], 2) }}</td>
                                <td class="py-2 px-3 text-right text-gray-800 tabular-nums">{{ $totals['register_to_role_rate'] }}</td>
                                <td class="py-2 px-3 text-right text-gray-800 tabular-nums">{{ $totals['register_to_pay_rate'] }}</td>
                                <td class="py-2 px-3 text-right text-gray-800 tabular-nums">{{ $totals['arppu'] === '-' ? '-' : number_format((float) $totals['arppu'], 2) }}</td>
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
