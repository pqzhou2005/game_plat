<?php
/**
 * @var \App\Filament\Pages\GameIntegrationCheck $this
 */
$gameConfig = $this->gameId ? \App\Models\GameSsoConfig::where('game_id', $this->gameId)->first() : null;
$recentLogs = $this->getRecentLogs();
?>

<x-filament-panels::page>
    <div class="space-y-6">
        <!-- 配置选择 -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">选择测试配置</h2>
            {{ $this->form }}
        </div>

        @if($gameConfig)
        <!-- 当前配置概览 -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">当前配置</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">平台ID</span>
                    <p class="font-medium">{{ $gameConfig->platform_id }}</p>
                </div>
                <div>
                    <span class="text-gray-500">登录地址</span>
                    <p class="font-medium truncate" title="{{ $gameConfig->login_url }}">{{ $gameConfig->login_url }}</p>
                </div>
                <div>
                    <span class="text-gray-500">通知地址</span>
                    <p class="font-medium truncate {{ $gameConfig->pay_notify_url ? '' : 'text-gray-400' }}" title="{{ $gameConfig->pay_notify_url ?? '' }}">
                        {{ $gameConfig->pay_notify_url ?: '未配置' }}
                    </p>
                </div>
                <div>
                    <span class="text-gray-500">lkey</span>
                    <p class="font-mono text-xs">{{ substr($gameConfig->login_key, 0, 8) }}****</p>
                </div>
                <div>
                    <span class="text-gray-500">payKey</span>
                    <p class="font-mono text-xs">{{ substr($gameConfig->pay_key, 0, 8) }}****</p>
                </div>
                <div>
                    <span class="text-gray-500">状态</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $gameConfig->enabled ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                        {{ $gameConfig->enabled ? '已启用' : '已禁用' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- 测试按钮 -->
        <div class="flex gap-3">
            <x-filament::button wire:click="testSso" color="primary">
                测试 SSO 登录
            </x-filament::button>
            <x-filament::button wire:click="testNotify" color="warning">
                测试通知 URL
            </x-filament::button>
        </div>

        <!-- SSO 测试结果 -->
        @if($ssoResult)
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">SSO 测试结果</h2>
            <div class="space-y-3">
                <div>
                    <label class="text-sm text-gray-500 block mb-1">完整登录 URL</label>
                    <div class="flex gap-2">
                        <input type="text" readonly value="{{ $ssoResult['url'] }}"
                            class="flex-1 bg-gray-50 border border-gray-200 rounded px-3 py-2 text-xs font-mono"
                            onclick="this.select()">
                        <button onclick="navigator.clipboard.writeText('{{ $ssoResult['url'] }}')"
                            class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded text-sm">复制</button>
                    </div>
                </div>
                <div>
                    <label class="text-sm text-gray-500 block mb-1">请求参数</label>
                    <div class="flex gap-2">
                        <input type="text" readonly value="{{ json_encode($ssoResult['params'], JSON_UNESCAPED_UNICODE) }}"
                            class="flex-1 bg-gray-50 border border-gray-200 rounded px-3 py-2 text-xs font-mono"
                            onclick="this.select()">
                        <button onclick="navigator.clipboard.writeText('{{ json_encode($ssoResult['params'], JSON_UNESCAPED_UNICODE) }}')"
                            class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded text-sm">复制</button>
                    </div>
                </div>
                <details class="text-sm">
                    <summary class="cursor-pointer text-gray-500 hover:text-gray-700">查看签名参数明细</summary>
                    <div class="mt-2 bg-gray-50 rounded p-3">
                        @foreach($ssoResult['params'] as $k => $v)
                        <div class="flex gap-2 py-1">
                            <span class="text-gray-500 w-24 shrink-0">{{ $k }}</span>
                            <span class="font-mono text-xs break-all">{{ $k === 'token' ? $v : $v }}</span>
                        </div>
                        @endforeach
                    </div>
                </details>
            </div>
        </div>
        @endif

        <!-- 通知测试结果 -->
        @if($notifyTestResult)
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">通知 URL 测试结果</h2>
            <div class="space-y-3 text-sm">
                <div class="flex items-center gap-2">
                    <span class="text-gray-500">状态:</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ ($notifyTestResult['success'] ?? false) ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                        {{ ($notifyTestResult['success'] ?? false) ? '可达' : '异常' }}
                    </span>
                    @if(isset($notifyTestResult['http_code']))
                    <span class="text-gray-500">HTTP {{ $notifyTestResult['http_code'] }}</span>
                    @endif
                </div>
                @if(isset($notifyTestResult['error']))
                <div class="bg-red-50 border border-red-200 text-red-700 rounded p-3">
                    {{ $notifyTestResult['error'] }}
                </div>
                @endif
                @if(isset($notifyTestResult['body_preview']))
                <div>
                    <label class="text-gray-500 block mb-1">响应内容</label>
                    <pre class="bg-gray-50 border rounded p-3 text-xs overflow-x-auto max-h-48">{{ $notifyTestResult['body_preview'] }}</pre>
                </div>
                @endif
            </div>
        </div>
        @endif
        @endif

        <!-- 最近发货日志 -->
        <div class="bg-white rounded-xl shadow-sm border p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                最近发货日志
                @if($recentLogs)
                <span class="text-sm font-normal text-gray-500">(最新20条)</span>
                @endif
            </h2>

            @if($recentLogs)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b text-left text-xs text-gray-500 uppercase tracking-wider">
                            <th class="px-3 py-2">时间</th>
                            <th class="px-3 py-2">状态</th>
                            <th class="px-3 py-2">HTTP</th>
                            <th class="px-3 py-2">订单号</th>
                            <th class="px-3 py-2">响应/错误</th>
                            <th class="px-3 py-2">参数</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentLogs as $log)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-3 py-2 text-xs text-gray-500 whitespace-nowrap">{{ $log['created_at'] }}</td>
                            <td class="px-3 py-2">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium
                                    {{ $log['status'] === \App\Enums\NotifyStatus::SUCCESS ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                    {{ $log['status'] === \App\Enums\NotifyStatus::SUCCESS ? '成功' : '失败' }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-xs">{{ $log['http_code'] ?? '-' }}</td>
                            <td class="px-3 py-2 text-xs font-mono">{{ $log['payment_order'] ? $log['payment_order']['order_no'] : '-' }}</td>
                            <td class="px-3 py-2 text-xs max-w-xs">
                                @if($log['response_body'])
                                <button onclick="alert({{ json_encode($log['response_body']) }})"
                                    class="text-blue-600 hover:underline">查看响应</button>
                                @elseif($log['error_message'])
                                <span class="text-red-600">{{ $log['error_message'] }}</span>
                                @else
                                -
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                @if($log['request_params'])
                                <button onclick="navigator.clipboard.writeText({{ json_encode(is_string($log['request_params']) ? $log['request_params'] : json_encode($log['request_params'])) }})"
                                    class="text-blue-600 hover:underline text-xs">复制参数</button>
                                @else
                                -
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-8 text-gray-400">
                @if($this->gameId)
                暂无发货日志
                @else
                请先选择游戏
                @endif
            </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
