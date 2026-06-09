<?php
/**
 * @var \App\Filament\Pages\Reconciliation $this
 */
$channelData = $this->getChannelData();
$gameDeliveryData = $this->getGameDeliveryData();
$abnormalOrders = $this->getAbnormalOrders();
?>

<x-filament-panels::page>
    <div x-data="{ tab: 'channel' }">
        <!-- Tabs -->
        <div class="flex space-x-1 rounded-lg bg-gray-100 p-1 mb-6 w-fit">
            <button @click="tab = 'channel'"
                :class="tab === 'channel' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-2 text-sm font-medium rounded-md transition">
                支付渠道对账
            </button>
            <button @click="tab = 'delivery'"
                :class="tab === 'delivery' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-2 text-sm font-medium rounded-md transition">
                游戏发货对账
            </button>
            <button @click="tab = 'abnormal'"
                :class="tab === 'abnormal' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500 hover:text-gray-700'"
                class="px-4 py-2 text-sm font-medium rounded-md transition">
                异常订单
                @if(count($abnormalOrders) > 0)
                <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                    {{ count($abnormalOrders) }}
                </span>
                @endif
            </button>
        </div>

        <!-- Tab: 支付渠道对账 -->
        <div x-show="tab === 'channel'" class="space-y-4">
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b text-left text-xs text-gray-500 uppercase tracking-wider">
                            <th class="px-4 py-3">日期</th>
                            <th class="px-4 py-3">渠道</th>
                            <th class="px-4 py-3 text-right">总订单</th>
                            <th class="px-4 py-3 text-right text-green-600">成功</th>
                            <th class="px-4 py-3 text-right text-red-600">失败</th>
                            <th class="px-4 py-3 text-right text-gray-500">退款</th>
                            <th class="px-4 py-3 text-right">金额(元)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($channelData as $row)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ $row['date'] }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $row['channel'] === 'alipay' ? 'bg-blue-50 text-blue-700' : 'bg-green-50 text-green-700' }}">
                                    {{ ['alipay' => '支付宝', 'wechat' => '微信支付'][$row['channel']] ?? $row['channel'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">{{ $row['total_orders'] }}</td>
                            <td class="px-4 py-3 text-right text-green-600">{{ $row['success_orders'] }}</td>
                            <td class="px-4 py-3 text-right text-red-600">{{ $row['failed_orders'] }}</td>
                            <td class="px-4 py-3 text-right text-gray-500">{{ $row['refund_orders'] }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ number_format($row['total_amount'], 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">暂无数据</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab: 游戏发货对账 -->
        <div x-show="tab === 'delivery'" class="space-y-4">
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b text-left text-xs text-gray-500 uppercase tracking-wider">
                            <th class="px-4 py-3">游戏</th>
                            <th class="px-4 py-3 text-right">总订单</th>
                            <th class="px-4 py-3 text-right text-green-600">已支付</th>
                            <th class="px-4 py-3 text-right text-green-600">已发货</th>
                            <th class="px-4 py-3 text-right text-red-600">发货失败</th>
                            <th class="px-4 py-3 text-right text-yellow-600">待发货</th>
                            <th class="px-4 py-3 text-right">金额(元)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($gameDeliveryData as $row)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ $row['game_name'] }}</td>
                            <td class="px-4 py-3 text-right">{{ $row['total_orders'] }}</td>
                            <td class="px-4 py-3 text-right text-green-600">{{ $row['pay_success'] }}</td>
                            <td class="px-4 py-3 text-right text-green-600">{{ $row['deliver_success'] }}</td>
                            <td class="px-4 py-3 text-right text-red-600">{{ $row['deliver_failed'] }}</td>
                            <td class="px-4 py-3 text-right text-yellow-600">{{ $row['deliver_pending'] }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ number_format($row['total_amount'], 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">暂无数据</td></tr>
                        @endforelse
                        @php
                            $totalAmount = collect($gameDeliveryData)->sum('total_amount');
                            $totalOrders = collect($gameDeliveryData)->sum('total_orders');
                            $totalPay = collect($gameDeliveryData)->sum('pay_success');
                            $totalDeliver = collect($gameDeliveryData)->sum('deliver_success');
                            $totalFail = collect($gameDeliveryData)->sum('deliver_failed');
                            $totalPending = collect($gameDeliveryData)->sum('deliver_pending');
                        @endphp
                        @if(count($gameDeliveryData) > 0)
                        <tr class="bg-gray-50 font-medium">
                            <td class="px-4 py-3">合计</td>
                            <td class="px-4 py-3 text-right">{{ $totalOrders }}</td>
                            <td class="px-4 py-3 text-right text-green-600">{{ $totalPay }}</td>
                            <td class="px-4 py-3 text-right text-green-600">{{ $totalDeliver }}</td>
                            <td class="px-4 py-3 text-right text-red-600">{{ $totalFail }}</td>
                            <td class="px-4 py-3 text-right text-yellow-600">{{ $totalPending }}</td>
                            <td class="px-4 py-3 text-right font-medium">{{ number_format($totalAmount, 2) }}</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tab: 异常订单 -->
        <div x-show="tab === 'abnormal'" class="space-y-4">
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b text-left text-xs text-gray-500 uppercase tracking-wider">
                            <th class="px-4 py-3">订单号</th>
                            <th class="px-4 py-3">用户</th>
                            <th class="px-4 py-3">金额</th>
                            <th class="px-4 py-3">异常类型</th>
                            <th class="px-4 py-3">时间</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($abnormalOrders as $order)
                        @php
                            $closed = \App\Enums\PaymentOrderStatus::CLOSED;
                            $success = \App\Enums\PaymentOrderStatus::SUCCESS;
                            $failed = \App\Enums\NotifyStatus::FAILED;
                            $pending = \App\Enums\NotifyStatus::PENDING;
                            if ($order->status === $closed) {
                                $type = '退款';
                                $color = 'bg-gray-100 text-gray-700';
                            } elseif ($order->notify_status === $failed) {
                                $type = '发货失败';
                                $color = 'bg-red-100 text-red-700';
                            } elseif ($order->notify_status === $pending && $order->status === $success) {
                                $type = '已支付未发货';
                                $color = 'bg-yellow-100 text-yellow-700';
                            } else {
                                $type = '其他';
                                $color = 'bg-gray-100 text-gray-700';
                            }
                        @endphp
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs">{{ $order->order_no }}</td>
                            <td class="px-4 py-3">{{ $order->user->username ?? '-' }}</td>
                            <td class="px-4 py-3">¥{{ number_format($order->amount, 2) }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $color }}">
                                    {{ $type }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ $order->created_at }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">暂无异常订单</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
