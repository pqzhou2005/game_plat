{{-- resources/views/filament/pages/user-investigation.blade.php --}}
<x-filament::page>
    {{-- 搜索区域 --}}
    <div class="space-y-6">
        <div class="flex gap-4 items-end">
            <div class="flex-1" wire:keydown.enter="search">
                {{ $this->form }}
            </div>
            <x-filament::button wire:click="search" color="primary">
                搜索
            </x-filament::button>
        </div>

        @if ($searched && !$user)
            {{-- 未找到 --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
                <div class="text-gray-400 text-lg mb-2">🔍</div>
                <p class="text-gray-500">未找到对应用户</p>
            </div>
        @endif

        @if ($user)
            {{-- ========== 1. 用户基础信息 ========== --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-user class="w-5 h-5 text-primary-500" />
                        用户基础信息
                    </div>
                </x-slot>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <p class="text-xs text-gray-500 mb-1">用户ID</p>
                        <p class="text-sm font-medium cursor-pointer hover:text-primary-500"
                           x-on:click="navigator.clipboard.writeText('{{ $user['id'] }}')"
                           title="点击复制">{{ $user['id'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">用户名</p>
                        <p class="text-sm font-medium cursor-pointer hover:text-primary-500"
                           x-on:click="navigator.clipboard.writeText('{{ $user['username'] }}')"
                           title="点击复制">{{ $user['username'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">手机号</p>
                        <p class="text-sm font-medium">{{ $user['masked_mobile'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">注册时间</p>
                        <p class="text-sm font-medium">{{ $user['created_at'] }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">账号状态</p>
                        <span class="px-2 py-0.5 text-xs rounded-full font-medium
                            {{ $user['status'] ? 'bg-success-100 text-success-700' : 'bg-danger-100 text-danger-700' }}">
                            {{ $user['status'] ? '启用' : '禁用' }}
                        </span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">最后登录时间</p>
                        <p class="text-sm font-medium">{{ $user['last_login_at'] ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">最后登录IP</p>
                        <p class="text-sm font-medium">{{ $user['last_login_ip'] ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1">实名状态</p>
                        <span class="px-2 py-0.5 text-xs rounded-full font-medium
                            {{ $user['real_name_badge']['color'] }}">
                            {{ $user['real_name_badge']['text'] }}
                        </span>
                    </div>
                </div>
            </x-filament::section>

            {{-- ========== 2. 注册来源 ========== --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-link class="w-5 h-5 text-primary-500" />
                        注册来源
                    </div>
                </x-slot>
                @if ($attribution)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">推广码</p>
                            <p class="text-sm font-medium">{{ $attribution['promote_code'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">推广入口名称</p>
                            <p class="text-sm font-medium">{{ $attribution['promote_name'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">关联游戏</p>
                            <p class="text-sm font-medium">{{ $attribution['game_name'] }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">归因时间</p>
                            <p class="text-sm font-medium">{{ $attribution['created_at'] }}</p>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-400">自然注册 / 未归因</p>
                @endif
            </x-filament::section>

            {{-- ========== 3. 登录记录 ========== --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-clock class="w-5 h-5 text-primary-500" />
                        登录记录（最近20条）
                    </div>
                </x-slot>
                @if (count($loginLogs) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">登录时间</th>
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">登录IP</th>
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">登录方式</th>
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">User-Agent</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($loginLogs as $log)
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-2 px-3 text-gray-700">{{ $log['created_at'] }}</td>
                                    <td class="py-2 px-3 text-gray-700">{{ $log['ip'] }}</td>
                                    <td class="py-2 px-3">
                                        <span class="text-xs font-medium px-2 py-0.5 rounded-full
                                            {{ $log['login_type'] === 'password' ? 'bg-blue-100 text-blue-700' : '' }}
                                            {{ $log['login_type'] === 'sms' ? 'bg-green-100 text-green-700' : '' }}
                                            {{ $log['login_type'] === 'oauth' ? 'bg-yellow-100 text-yellow-700' : '' }}">
                                            {{ $log['login_type_label'] }}
                                        </span>
                                    </td>
                                    <td class="py-2 px-3 text-gray-500 max-w-xs truncate" title="{{ $log['user_agent'] }}">{{ $log['user_agent'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-400">暂无登录记录</p>
                @endif
            </x-filament::section>

            {{-- ========== 4. 角色记录 ========== --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-cube class="w-5 h-5 text-primary-500" />
                        角色记录
                    </div>
                </x-slot>
                @if (count($roleReports) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">游戏</th>
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">区服</th>
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">角色ID</th>
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">角色名</th>
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">等级</th>
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">创角时间</th>
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">最后上报</th>
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">最后操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($roleReports as $role)
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-2 px-3 text-gray-700">{{ $role['game_name'] }}</td>
                                    <td class="py-2 px-3 text-gray-700">{{ $role['server_name'] }}</td>
                                    <td class="py-2 px-3 text-gray-700 font-mono text-xs">{{ $role['role_id'] }}</td>
                                    <td class="py-2 px-3 text-gray-700">{{ $role['role_name'] }}</td>
                                    <td class="py-2 px-3 text-gray-700">{{ $role['role_level'] }}</td>
                                    <td class="py-2 px-3 text-gray-700">{{ $role['create_time'] }}</td>
                                    <td class="py-2 px-3 text-gray-700">{{ $role['last_report_at'] }}</td>
                                    <td class="py-2 px-3">
                                        <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">
                                            {{ $role['submit_type_label'] }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-400">暂无角色记录</p>
                @endif
            </x-filament::section>

            {{-- ========== 5. 订单记录 ========== --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-currency-yen class="w-5 h-5 text-primary-500" />
                        订单记录
                    </div>
                </x-slot>
                @if (count($orders) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">订单号</th>
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">游戏</th>
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">区服</th>
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">角色</th>
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">金额</th>
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">订单状态</th>
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">支付时间</th>
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">发货状态</th>
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($orders as $order)
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-2 px-3">
                                        <a href="{{ $order['detail_url'] }}" class="text-primary-600 hover:text-primary-800 font-mono text-xs cursor-pointer">
                                            {{ $order['order_no'] }}
                                        </a>
                                    </td>
                                    <td class="py-2 px-3 text-gray-700">{{ $order['game_name'] }}</td>
                                    <td class="py-2 px-3 text-gray-700">{{ $order['server_id'] }}</td>
                                    <td class="py-2 px-3 text-gray-700">{{ $order['role_name'] }}</td>
                                    <td class="py-2 px-3 text-gray-700">{{ $order['amount'] }}</td>
                                    <td class="py-2 px-3">
                                        <span class="px-2 py-0.5 text-xs rounded-full font-medium {{ $order['status_badge']['color'] }}">
                                            {{ $order['status_badge']['text'] }}
                                        </span>
                                    </td>
                                    <td class="py-2 px-3 text-gray-700">{{ $order['paid_at'] }}</td>
                                    <td class="py-2 px-3">
                                        <span class="px-2 py-0.5 text-xs rounded-full font-medium {{ $order['notify_badge']['color'] }}">
                                            {{ $order['notify_badge']['text'] }}
                                        </span>
                                    </td>
                                    <td class="py-2 px-3">
                                        <a href="{{ $order['reconciliation_url'] }}" class="text-primary-600 hover:text-primary-800 text-xs">
                                            对账
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-400">暂无订单记录</p>
                @endif
            </x-filament::section>

            {{-- ========== 6. 发货失败订单 ========== --}}
            @if (count($failedDeliveries) > 0)
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2 text-danger-600">
                        <x-heroicon-o-exclamation-triangle class="w-5 h-5" />
                        发货失败订单
                    </div>
                </x-slot>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-danger-200">
                                <th class="text-left py-2 px-3 text-danger-700 font-medium">订单号</th>
                                <th class="text-left py-2 px-3 text-danger-700 font-medium">金额</th>
                                <th class="text-left py-2 px-3 text-danger-700 font-medium">失败原因</th>
                                <th class="text-left py-2 px-3 text-danger-700 font-medium">最后响应</th>
                                <th class="text-left py-2 px-3 text-danger-700 font-medium">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($failedDeliveries as $fail)
                            <tr class="border-b border-danger-100 hover:bg-danger-50">
                                <td class="py-2 px-3">
                                    <a href="{{ $fail['detail_url'] }}" class="text-danger-600 hover:text-danger-800 font-mono text-xs cursor-pointer">
                                        {{ $fail['order_no'] }}
                                    </a>
                                </td>
                                <td class="py-2 px-3 text-gray-700">{{ $fail['amount'] }}</td>
                                <td class="py-2 px-3 text-gray-700 max-w-xs break-words">{{ $fail['error_message'] }}</td>
                                <td class="py-2 px-3 text-gray-500 max-w-xs truncate text-xs" title="{{ $fail['response_body'] }}">{{ $fail['response_body'] }}</td>
                                <td class="py-2 px-3">
                                    <a href="{{ $fail['reconciliation_url'] }}" class="text-danger-600 hover:text-danger-800 text-xs">
                                        前往对账页处理
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
            @endif
        @endif
    </div>
</x-filament::page>
