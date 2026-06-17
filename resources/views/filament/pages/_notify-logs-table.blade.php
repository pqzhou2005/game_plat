{{--
    resources/views/filament/pages/_notify-logs-table.blade.php
    发货日志表格（局部模板）
    @param array $logs — GameNotifyLog 数组
--}}
<div class="overflow-x-auto">
    <table class="w-full text-sm whitespace-nowrap">
        <thead>
            <tr class="border-b border-gray-200">
                <th class="text-left py-2 px-3 text-gray-500 font-medium">时间</th>
                <th class="text-left py-2 px-3 text-gray-500 font-medium">状态</th>
                <th class="text-left py-2 px-3 text-gray-500 font-medium">HTTP</th>
                <th class="text-left py-2 px-3 text-gray-500 font-medium">订单号</th>
                <th class="text-left py-2 px-3 text-gray-500 font-medium">响应/错误</th>
                <th class="text-left py-2 px-3 text-gray-500 font-medium">参数</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($logs as $log)
                @php
                    $logStatus = is_string($log['status'] ?? null) ? $log['status'] : ($log['status']->value ?? '');
                    $isSuccess = $logStatus === \App\Enums\NotifyStatus::SUCCESS;
                @endphp
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="py-2 px-3 text-xs text-gray-500">{{ $log['created_at'] ?? '-' }}</td>
                    <td class="py-2 px-3">
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium
                            {{ $isSuccess ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                            {{ $isSuccess ? '成功' : '失败' }}
                        </span>
                    </td>
                    <td class="py-2 px-3 text-xs">{{ $log['http_code'] ?? '-' }}</td>
                    <td class="py-2 px-3 text-xs font-mono">
                        <span title="{{ $log['payment_order']['order_no'] ?? '' }}">
                            {{ \Illuminate\Support\Str::limit($log['payment_order']['order_no'] ?? '-', 20) }}
                        </span>
                    </td>
                    <td class="py-2 px-3 text-xs max-w-xs truncate">
                        @if (!empty($log['response_body']))
                            <button onclick="alert({{ json_encode(mb_substr($log['response_body'], 0, 500)) }})"
                                class="text-primary-600 hover:underline">查看响应</button>
                        @elseif (!empty($log['error_message']))
                            <span class="text-danger-600" title="{{ $log['error_message'] }}">{{ \Illuminate\Support\Str::limit($log['error_message'], 40) }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td class="py-2 px-3">
                        @if (!empty($log['request_params']))
                            <button onclick="navigator.clipboard.writeText({{ json_encode(is_string($log['request_params']) ? $log['request_params'] : json_encode($log['request_params'])) }})"
                                class="text-primary-600 hover:underline text-xs">复制参数</button>
                        @else
                            -
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="mt-2 text-xs text-gray-400">
        共 {{ count($logs) }} 条记录
    </div>
</div>
