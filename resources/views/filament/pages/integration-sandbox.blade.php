{{--
    resources/views/filament/pages/integration-sandbox.blade.php
    接入沙箱 — 5 个 Tab：接入概览 / SSO 登录 / 支付发货 / 角色上报 / 开服上报·文档
--}}
<x-filament::page>
    @php
        $ssoExample = $this->gameId ? \App\Models\GameSsoConfig::where('game_id', $this->gameId)->first() : null;
        $roleExample = $this->getRoleReportExample();
        $serverExample = $this->getServerOpenExample();
        $integrationDoc = $this->getIntegrationDoc();
        $recentLogs = $this->getRecentNotifyLogs();
        $gameServers = $this->getGameServers();
    @endphp

    {{-- ==================== Tab 导航 ==================== --}}
    <div class="border-b border-gray-200 mb-6" x-data="{ tab: '{{ $activeTab }}' }">
        <nav class="flex gap-0 -mb-px">
            <button wire:click="switchTab('overview')"
                class="px-5 py-3 text-sm font-medium border-b-2 transition-colors
                {{ $activeTab === 'overview' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                <x-heroicon-o-clipboard-document-list class="w-4 h-4 inline-block -mt-0.5 mr-1.5" />
                接入概览
            </button>
            <button wire:click="switchTab('sso')"
                class="px-5 py-3 text-sm font-medium border-b-2 transition-colors
                {{ $activeTab === 'sso' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                <x-heroicon-o-key class="w-4 h-4 inline-block -mt-0.5 mr-1.5" />
                SSO 登录
            </button>
            <button wire:click="switchTab('pay')"
                class="px-5 py-3 text-sm font-medium border-b-2 transition-colors
                {{ $activeTab === 'pay' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                <x-heroicon-o-currency-yen class="w-4 h-4 inline-block -mt-0.5 mr-1.5" />
                支付发货
            </button>
            <button wire:click="switchTab('role')"
                class="px-5 py-3 text-sm font-medium border-b-2 transition-colors
                {{ $activeTab === 'role' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                <x-heroicon-o-user-group class="w-4 h-4 inline-block -mt-0.5 mr-1.5" />
                角色上报
            </button>
            <button wire:click="switchTab('server')"
                class="px-5 py-3 text-sm font-medium border-b-2 transition-colors
                {{ $activeTab === 'server' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                <x-heroicon-o-document-text class="w-4 h-4 inline-block -mt-0.5 mr-1.5" />
                开服上报 / 文档
            </button>
        </nav>
    </div>

    {{-- ============================================================ --}}
    {{-- Tab 1: 接入概览 --}}
    {{-- ============================================================ --}}
    @if ($activeTab === 'overview')
        <div class="space-y-4">
            <p class="text-sm text-gray-500">各游戏接入状态一览，绿色=正常，黄色=缺少配置，红色=异常。</p>

            <div class="overflow-x-auto">
                <table class="w-full text-sm whitespace-nowrap">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-2 px-3 text-gray-500 font-medium">游戏</th>
                            <th class="text-center py-2 px-3 text-gray-500 font-medium">SSO 配置</th>
                            <th class="text-center py-2 px-3 text-gray-500 font-medium">登录地址</th>
                            <th class="text-center py-2 px-3 text-gray-500 font-medium">支付回调</th>
                            <th class="text-center py-2 px-3 text-gray-500 font-medium">最近发货</th>
                            <th class="text-center py-2 px-3 text-gray-500 font-medium">最近角色上报</th>
                            <th class="text-center py-2 px-3 text-gray-500 font-medium">最近开服上报</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($overviewData as $row)
                            @php
                                $statusColor = fn(bool $ok) => $ok
                                    ? 'bg-green-100 text-green-700'
                                    : 'bg-yellow-100 text-yellow-700';
                                $notifyOk = $row['last_notify_status'] === \App\Enums\NotifyStatus::SUCCESS;
                                $notifyColor = $row['last_notify_status']
                                    ? ($notifyOk ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700')
                                    : 'bg-gray-100 text-gray-500';
                                $notifyText = $row['last_notify_status']
                                    ? ($notifyOk ? '成功' : '失败')
                                    : '无记录';
                            @endphp
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-2.5 px-3 font-medium text-gray-800">{{ $row['name'] }}</td>
                                <td class="py-2.5 px-3 text-center">
                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-medium {{ $statusColor($row['has_config']) }}">
                                        {{ $row['has_config'] ? '已配置' : '未配置' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center">
                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-medium {{ $statusColor($row['has_login_url']) }}">
                                        {{ $row['has_login_url'] ? '已配' : '未配' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center">
                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-medium {{ $statusColor($row['has_notify_url']) }}">
                                        {{ $row['has_notify_url'] ? '已配' : '未配' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-center">
                                    <div class="flex flex-col items-center gap-0.5">
                                        <span class="inline-block px-2 py-0.5 rounded text-xs font-medium {{ $notifyColor }}">
                                            {{ $notifyText }}
                                        </span>
                                        @if ($row['last_notify_time'])
                                            <span class="text-xs text-gray-400">{{ $row['last_notify_time'] }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-2.5 px-3 text-center text-xs">
                                    @if ($row['last_role_report_time'])
                                        <span class="text-gray-600">{{ $row['last_role_report_time'] }}</span>
                                    @else
                                        <span class="text-gray-400">无记录</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-center text-xs">
                                    @if ($row['last_server_open_time'])
                                        <span class="text-gray-600">{{ $row['last_server_open_time'] }}</span>
                                    @else
                                        <span class="text-gray-400">无记录</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-gray-400">暂无游戏数据</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    {{-- ============================================================ --}}
    {{-- Tab 2: SSO 登录 --}}
    {{-- ============================================================ --}}
    @elseif ($activeTab === 'sso')
        <div class="space-y-6">
            {{-- 表单 --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-play class="w-5 h-5 text-primary-500" />
                        SSO 登录调试
                    </div>
                </x-slot>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="w-full">
                        {{ $this->form->getComponent('gameId') }}
                    </div>
                    <div>
                        <x-filament::input.wrapper label="测试用户ID">
                            <x-filament::input type="number" wire:model="ssoUserId" placeholder="默认 1" />
                        </x-filament::input.wrapper>
                    </div>
                    <div>
                        <x-filament::input.wrapper label="区服ID">
                            <x-filament::input type="number" wire:model="ssoServerId" placeholder="默认 1" />
                        </x-filament::input.wrapper>
                    </div>
                </div>

                <div class="mt-4">
                    <x-filament::button wire:click="runSsoTest" color="primary" icon="heroicon-o-play">
                        生成 SSO URL
                    </x-filament::button>
                    @if ($ssoResult)
                        <x-filament::button onclick="window.open('{{ $ssoResult['url'] }}', '_blank')"
                            color="success" icon="heroicon-o-arrow-top-right-on-square" class="ml-2">
                            打开 iframe 测试
                        </x-filament::button>
                    @endif
                </div>
            </x-filament::section>

            {{-- 调试结果 --}}
            @if ($ssoDebugInfo)
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-document-text class="w-5 h-5 text-success-500" />
                            调试信息
                        </div>
                    </x-slot>

                    <div class="space-y-4">
                        {{-- 完整 URL --}}
                        <div>
                            <label class="text-sm text-gray-500 block mb-1 font-medium">完整登录 URL</label>
                            <div class="flex gap-2">
                                <input type="text" readonly value="{{ $ssoDebugInfo['iframe_url'] }}"
                                    class="flex-1 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-xs font-mono"
                                    onclick="this.select()">
                                <button onclick="navigator.clipboard.writeText('{{ $ssoDebugInfo['iframe_url'] }}')"
                                    class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm whitespace-nowrap">复制</button>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {{-- 测试信息 --}}
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-gray-700 mb-2">测试信息</h4>
                                <dl class="space-y-1.5 text-sm">
                                    <div class="flex gap-2">
                                        <dt class="text-gray-500 w-24 shrink-0">游戏：</dt>
                                        <dd class="text-gray-800">{{ \App\Models\Game::find($this->gameId)?->name ?? '-' }}</dd>
                                    </div>
                                    <div class="flex gap-2">
                                        <dt class="text-gray-500 w-24 shrink-0">用户：</dt>
                                        <dd class="text-gray-800">{{ $ssoDebugInfo['username'] }} (ID: {{ $ssoDebugInfo['user_id'] }})</dd>
                                    </div>
                                    <div class="flex gap-2">
                                        <dt class="text-gray-500 w-24 shrink-0">区服：</dt>
                                        <dd class="text-gray-800">ID {{ $ssoDebugInfo['server_id'] }}</dd>
                                    </div>
                                    <div class="flex gap-2">
                                        <dt class="text-gray-500 w-24 shrink-0">平台ID：</dt>
                                        <dd class="text-gray-800">{{ $ssoDebugInfo['platform_id'] }}</dd>
                                    </div>
                                </dl>
                            </div>

                            {{-- 参数明细 --}}
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-gray-700 mb-2">原始参数（排序后）</h4>
                                <dl class="space-y-1.5 text-sm">
                                    @foreach ($ssoDebugInfo['sorted_params'] as $k => $v)
                                        <div class="flex gap-2">
                                            <dt class="text-gray-500 font-mono text-xs w-28 shrink-0">{{ $k }}</dt>
                                            <dd class="text-gray-800 font-mono text-xs break-all">{{ $v }}</dd>
                                        </div>
                                    @endforeach
                                    <div class="flex gap-2 pt-1 border-t border-gray-200 mt-1">
                                        <dt class="text-gray-500 font-mono text-xs w-28 shrink-0">token</dt>
                                        <dd class="text-gray-800 font-mono text-xs break-all">{{ $ssoDebugInfo['md5_result'] }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        {{-- 签名过程 --}}
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">签名计算过程</h4>
                            <div class="space-y-2 text-sm font-mono text-xs">
                                <div>
                                    <label class="text-gray-500 block mb-0.5">① 排序后参数字符串</label>
                                    <div class="flex gap-2">
                                        <input type="text" readonly value="{{ $ssoDebugInfo['query_string'] }}"
                                            class="flex-1 bg-white border border-gray-200 rounded px-2 py-1.5"
                                            onclick="this.select()">
                                        <button onclick="navigator.clipboard.writeText('{{ $ssoDebugInfo['query_string'] }}')"
                                            class="px-2 py-1 bg-gray-200 hover:bg-gray-300 rounded text-xs">复制</button>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-gray-500 block mb-0.5">② 尾部追加 lkey（已脱敏）</label>
                                    <div class="bg-white border border-gray-200 rounded px-2 py-1.5 break-all">
                                        <span class="text-gray-800">{{ $ssoDebugInfo['query_string'] }}</span><span class="text-red-500 font-bold">{{ $ssoDebugInfo['lkey_masked'] }}</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-gray-500 block mb-0.5">③ MD5 小写 → token</label>
                                    <div class="flex gap-2">
                                        <input type="text" readonly value="{{ $ssoDebugInfo['md5_result'] }}"
                                            class="flex-1 bg-white border border-green-200 rounded px-2 py-1.5 text-green-700 font-bold"
                                            onclick="this.select()">
                                        <button onclick="navigator.clipboard.writeText('{{ $ssoDebugInfo['md5_result'] }}')"
                                            class="px-2 py-1 bg-gray-200 hover:bg-gray-300 rounded text-xs">复制</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 完整密钥 --}}
                        <details class="text-sm">
                            <summary class="cursor-pointer text-gray-500 hover:text-gray-700 font-medium">查看完整 lkey</summary>
                            <div class="mt-2 bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                                <label class="text-xs text-yellow-700 block mb-1">完整 lkey（请勿泄露）</label>
                                <div class="flex gap-2">
                                    <input type="text" readonly value="{{ $ssoDebugInfo['login_key_full'] }}"
                                        class="flex-1 bg-white border border-yellow-200 rounded px-3 py-2 text-xs font-mono" onclick="this.select()">
                                    <button onclick="navigator.clipboard.writeText('{{ $ssoDebugInfo['login_key_full'] }}')"
                                        class="px-3 py-2 bg-yellow-100 hover:bg-yellow-200 rounded text-xs">复制</button>
                                </div>
                            </div>
                        </details>
                    </div>
                </x-filament::section>
            @endif

            {{-- 最近发货日志 --}}
            @if (count($recentLogs) > 0)
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-clock class="w-5 h-5 text-gray-500" />
                            最近发货日志
                        </div>
                    </x-slot>
                    @include('filament.pages._notify-logs-table', ['logs' => $recentLogs])
                </x-filament::section>
            @endif
        </div>

    {{-- ============================================================ --}}
    {{-- Tab 3: 支付发货 --}}
    {{-- ============================================================ --}}
    @elseif ($activeTab === 'pay')
        <div class="space-y-6">
            {{-- 表单 --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-play class="w-5 h-5 text-primary-500" />
                        支付发货模拟
                    </div>
                </x-slot>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        {{ $this->form->getComponent('gameId') }}
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 block mb-1">用户ID <span class="text-red-500">*</span></label>
                        <x-filament::input type="number" wire:model="payUserId" placeholder="输入用户ID" />
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 block mb-1">金额（元）<span class="text-red-500">*</span></label>
                        <x-filament::input type="number" step="0.01" wire:model="payAmount" placeholder="例: 6.00" />
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 block mb-1">角色ID</label>
                        <x-filament::input wire:model="payRoleId" placeholder="可选" />
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 block mb-1">商品ID</label>
                        <x-filament::input wire:model="payProductId" placeholder="默认 test_product" />
                    </div>
                </div>

                <div class="mt-4">
                    <x-filament::button wire:click="runPaySimulation" color="warning" icon="heroicon-o-play">
                        创建测试订单并模拟发货
                    </x-filament::button>
                    <span class="ml-3 text-xs text-gray-400">将创建一笔测试订单并调用游戏方发货接口</span>
                </div>
            </x-filament::section>

            {{-- 模拟结果 --}}
            @if ($payResult)
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-document-text class="w-5 h-5 {{ $payResult['success'] ? 'text-success-500' : 'text-danger-500' }}" />
                            发货结果
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                {{ $payResult['success'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $payResult['success'] ? '成功' : '失败' }}
                            </span>
                        </div>
                    </x-slot>

                    <div class="space-y-3 text-sm">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div class="bg-gray-50 rounded-lg p-3">
                                <span class="text-gray-500 text-xs block">订单号</span>
                                <span class="font-mono text-sm font-medium">{{ $payResult['order_no'] }}</span>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <span class="text-gray-500 text-xs block">请求 URL</span>
                                <span class="font-mono text-xs break-all">{{ $payResult['notify_url'] }}</span>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <span class="text-gray-500 text-xs block">HTTP 状态码</span>
                                <span class="font-mono text-sm font-medium">{{ $payResult['http_code'] ?? '-' }}</span>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-3">
                                <span class="text-gray-500 text-xs block">测试订单</span>
                                <span class="text-xs font-medium text-yellow-600">是（不会计入正式报表）</span>
                            </div>
                        </div>

                        {{-- 发货参数 --}}
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">发货参数（排序后）</h4>
                            <div class="overflow-x-auto">
                                <table class="w-full text-xs font-mono">
                                    <thead>
                                        <tr class="border-b border-gray-200">
                                            <th class="text-left py-1 pr-4 text-gray-500">参数</th>
                                            <th class="text-left py-1 text-gray-500">值</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($payResult['notify_params'] as $k => $v)
                                            <tr class="border-b border-gray-100">
                                                <td class="py-1 pr-4 text-gray-600">{{ $k }}</td>
                                                <td class="py-1 break-all">{{ $v }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- 签名过程 --}}
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">签名计算</h4>
                            <div class="space-y-2 text-xs font-mono">
                                <div>
                                    <span class="text-gray-500">签名串：</span>
                                    <span>{{ http_build_query($payResult['notify_params']) }}<span class="text-red-500">[payKey]</span></span>
                                </div>
                                <div>
                                    <span class="text-gray-500">payKey（已脱敏）：</span>
                                    <span>{{ $payResult['pay_key_masked'] }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">MD5 结果（sign）：</span>
                                    <span class="text-green-700 font-bold">{{ $payResult['sign'] }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- 游戏方响应 --}}
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-700 mb-2">游戏方响应</h4>
                            @if ($payResult['error_message'])
                                <div class="bg-red-50 border border-red-200 text-red-700 rounded p-3 text-xs">
                                    错误：{{ $payResult['error_message'] }}
                                </div>
                            @else
                                <pre class="bg-white border border-gray-200 rounded p-3 text-xs overflow-x-auto max-h-48">{{ $payResult['response_body'] }}</pre>
                            @endif
                        </div>

                        {{-- 完整 payKey --}}
                        <details class="text-sm">
                            <summary class="cursor-pointer text-gray-500 hover:text-gray-700 font-medium">查看完整 payKey</summary>
                            <div class="mt-2 bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                                <label class="text-xs text-yellow-700 block mb-1">完整 payKey（请勿泄露）</label>
                                <div class="flex gap-2">
                                    <input type="text" readonly value="{{ $payResult['pay_key_full'] }}"
                                        class="flex-1 bg-white border border-yellow-200 rounded px-3 py-2 text-xs font-mono" onclick="this.select()">
                                    <button onclick="navigator.clipboard.writeText('{{ $payResult['pay_key_full'] }}')"
                                        class="px-3 py-2 bg-yellow-100 hover:bg-yellow-200 rounded text-xs">复制</button>
                                </div>
                            </div>
                        </details>
                    </div>
                </x-filament::section>
            @endif

            {{-- 最近发货日志 --}}
            @if (count($recentLogs) > 0)
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-clock class="w-5 h-5 text-gray-500" />
                            最近发货日志
                        </div>
                    </x-slot>
                    @include('filament.pages._notify-logs-table', ['logs' => $recentLogs])
                </x-filament::section>
            @endif
        </div>

    {{-- ============================================================ --}}
    {{-- Tab 4: 角色上报 --}}
    {{-- ============================================================ --}}
    @elseif ($activeTab === 'role')
        <div class="space-y-6">
            {{-- JSON 示例 --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-code-bracket class="w-5 h-5 text-primary-500" />
                        角色上报接口说明
                    </div>
                </x-slot>

                <div class="text-sm text-gray-600 mb-4">
                    <p><strong>接口地址：</strong><code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs font-mono">{{ $roleExample['endpoint'] }}</code></p>
                    <p><strong>鉴权方式：</strong>{{ $roleExample['auth'] }}</p>
                    <p><strong>Content-Type：</strong>{{ $roleExample['content_type'] }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-2">请求参数</h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="bg-gray-50 border-b text-left">
                                        <th class="px-2 py-1.5 text-gray-500">参数</th>
                                        <th class="px-2 py-1.5 text-gray-500">类型</th>
                                        <th class="px-2 py-1.5 text-gray-500">必填</th>
                                        <th class="px-2 py-1.5 text-gray-500">说明</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($roleExample['params'] as $pk => $pv)
                                        <tr class="border-b border-gray-100">
                                            <td class="px-2 py-1.5 font-mono">{{ $pk }}</td>
                                            <td class="px-2 py-1.5 text-gray-600">{{ $pv['type'] }}</td>
                                            <td class="px-2 py-1.5">{{ $pv['required'] ? '✅' : '' }}</td>
                                            <td class="px-2 py-1.5 text-gray-600">{{ $pv['desc'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-2">JSON 示例</h4>
                        <div class="relative">
                            <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs overflow-x-auto max-h-80">@json(json_decode($roleExample['example_json']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)</pre>
                            <button onclick="navigator.clipboard.writeText({{ json_encode($roleExample['example_json']) }})"
                                class="absolute top-2 right-2 px-2 py-1 bg-gray-700 hover:bg-gray-600 text-gray-200 rounded text-xs">复制</button>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">响应：<code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs">{{ $roleExample['response_success'] }}</code></p>
                    </div>
                </div>

                {{-- 批量上报 --}}
                <details class="mt-4 text-sm">
                    <summary class="cursor-pointer text-gray-500 hover:text-gray-700 font-medium">批量上报接口</summary>
                    <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600 mb-1"><strong>接口地址：</strong><code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs font-mono">{{ $roleExample['batch_endpoint'] }}</code></p>
                            <p class="text-sm text-gray-600 mb-1"><strong>格式：</strong>JSON 数组</p>
                        </div>
                        <div class="relative">
                            <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs overflow-x-auto">@json(json_decode($roleExample['batch_example']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)</pre>
                            <button onclick="navigator.clipboard.writeText({{ json_encode($roleExample['batch_example']) }})"
                                class="absolute top-2 right-2 px-2 py-1 bg-gray-700 hover:bg-gray-600 text-gray-200 rounded text-xs">复制</button>
                        </div>
                    </div>
                </details>
            </x-filament::section>

            {{-- 最近角色上报记录 --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-clock class="w-5 h-5 text-gray-500" />
                        最近角色上报记录
                    </div>
                </x-slot>

                <div class="mb-3">
                    <div class="w-64">
                        {{ $this->form->getComponent('gameId') }}
                    </div>
                </div>

                @if (count($roleReports) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm whitespace-nowrap">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">时间</th>
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">用户ID</th>
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">类型</th>
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">角色ID/名称</th>
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">区服</th>
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">等级</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($roleReports as $r)
                                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                                        <td class="py-2 px-3 text-xs text-gray-500">{{ $r['reported_at'] }}</td>
                                        <td class="py-2 px-3 font-mono text-xs">{{ $r['user_id'] }}</td>
                                        <td class="py-2 px-3">
                                            <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700">{{ $r['submit_type_label'] }}</span>
                                        </td>
                                        <td class="py-2 px-3">
                                            <span class="font-mono text-xs">{{ $r['role_id'] }}</span>
                                            @if ($r['role_name'] !== '-')
                                                <span class="text-gray-500 text-xs">/ {{ $r['role_name'] }}</span>
                                            @endif
                                        </td>
                                        <td class="py-2 px-3 text-xs text-gray-600">{{ $r['server_name'] }} ({{ $r['server_id'] }})</td>
                                        <td class="py-2 px-3 text-xs">{{ $r['role_level'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="py-6 text-center text-gray-400 text-sm">
                        {{ $this->gameId ? '暂无角色上报记录' : '请先选择游戏' }}
                    </div>
                @endif
            </x-filament::section>
        </div>

    {{-- ============================================================ --}}
    {{-- Tab 5: 开服上报 / 文档 --}}
    {{-- ============================================================ --}}
    @elseif ($activeTab === 'server')
        <div class="space-y-6">
            {{-- 开服上报接口 --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-code-bracket class="w-5 h-5 text-primary-500" />
                        开服上报接口说明
                    </div>
                </x-slot>

                <div class="text-sm text-gray-600 mb-4">
                    <p><strong>接口地址：</strong><code class="bg-gray-100 px-1.5 py-0.5 rounded text-xs font-mono">{{ $serverExample['endpoint'] }}</code></p>
                    <p><strong>鉴权方式：</strong>{{ $serverExample['auth'] }}</p>
                    <p><strong>签名算法：</strong>{{ $serverExample['sign_algorithm'] }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-2">请求参数</h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="bg-gray-50 border-b text-left">
                                        <th class="px-2 py-1.5 text-gray-500">参数</th>
                                        <th class="px-2 py-1.5 text-gray-500">类型</th>
                                        <th class="px-2 py-1.5 text-gray-500">必填</th>
                                        <th class="px-2 py-1.5 text-gray-500">说明</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($serverExample['params'] as $pk => $pv)
                                        <tr class="border-b border-gray-100">
                                            <td class="px-2 py-1.5 font-mono">{{ $pk }}</td>
                                            <td class="px-2 py-1.5 text-gray-600">{{ $pv['type'] }}</td>
                                            <td class="px-2 py-1.5">{{ $pv['required'] ? '✅' : '' }}</td>
                                            <td class="px-2 py-1.5 text-gray-600">{{ $pv['desc'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-700 mb-2">请求示例</h4>
                        <div class="relative">
                            <pre class="bg-gray-900 text-gray-100 rounded-lg p-4 text-xs overflow-x-auto">@json(json_decode($serverExample['example_json']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)</pre>
                            <button onclick="navigator.clipboard.writeText({{ json_encode($serverExample['example_json']) }})"
                                class="absolute top-2 right-2 px-2 py-1 bg-gray-700 hover:bg-gray-600 text-gray-200 rounded text-xs">复制</button>
                        </div>
                        <div class="mt-2 space-y-1">
                            <p class="text-xs text-gray-500">成功响应：<code class="bg-green-50 text-green-700 px-1.5 py-0.5 rounded text-xs">{{ $serverExample['response_success'] }}</code></p>
                            <p class="text-xs text-gray-500">失败响应：<code class="bg-red-50 text-red-700 px-1.5 py-0.5 rounded text-xs">{{ $serverExample['response_fail'] }}</code></p>
                        </div>
                    </div>
                </div>
            </x-filament::section>

            {{-- 最近开服上报 --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-clock class="w-5 h-5 text-gray-500" />
                        最近开服上报记录
                    </div>
                </x-slot>

                <div class="mb-3">
                    <div class="w-64">
                        {{ $this->form->getComponent('gameId') }}
                    </div>
                </div>

                @if (count($serverOpenReports) > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm whitespace-nowrap">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">上报时间</th>
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">平台ID</th>
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">开服编号</th>
                                    <th class="text-left py-2 px-3 text-gray-500 font-medium">开服时间</th>
                                    <th class="text-right py-2 px-3 text-gray-500 font-medium">创角数</th>
                                    <th class="text-right py-2 px-3 text-gray-500 font-medium">付费数</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($serverOpenReports as $r)
                                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                                        <td class="py-2 px-3 text-xs text-gray-500">{{ $r['reported_at'] }}</td>
                                        <td class="py-2 px-3 font-mono text-xs">{{ $r['project'] }}</td>
                                        <td class="py-2 px-3 font-mono text-xs">{{ $r['open_server'] }}</td>
                                        <td class="py-2 px-3 text-xs">{{ $r['open_server_time'] }}</td>
                                        <td class="py-2 px-3 text-right text-xs tabular-nums">{{ number_format($r['created_role_num']) }}</td>
                                        <td class="py-2 px-3 text-right text-xs tabular-nums">{{ number_format($r['pay_num']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="py-6 text-center text-gray-400 text-sm">
                        {{ $this->gameId ? '暂无开服上报记录' : '请先选择游戏' }}
                    </div>
                @endif
            </x-filament::section>

            {{-- 接入文档 --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-document-text class="w-5 h-5 text-primary-500" />
                        接入文档
                    </div>
                </x-slot>

                @if ($integrationDoc)
                    <div class="mb-3">
                        <x-filament::button onclick="window.print()" color="gray" icon="heroicon-o-printer" size="sm" class="mr-2">
                            打印 / 导出 PDF
                        </x-filament::button>
                        <button onclick="copyDocContent()"
                            class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm inline-flex items-center gap-1">
                            <x-heroicon-o-clipboard class="w-4 h-4" />
                            复制全部
                        </button>
                    </div>

                    <div id="integration-doc" class="bg-white border border-gray-200 rounded-xl p-6 text-sm space-y-6">
                        {{-- 标题 --}}
                        <div class="text-center border-b border-gray-200 pb-4">
                            <h2 class="text-lg font-bold text-gray-900">游戏接入说明</h2>
                            <p class="text-gray-500 mt-1">游戏：{{ $integrationDoc['game_name'] }}</p>
                            <p class="text-gray-400 text-xs mt-1">生成时间：{{ now()->format('Y-m-d H:i:s') }}</p>
                        </div>

                        {{-- 基本信息 --}}
                        <div>
                            <h3 class="text-base font-semibold text-gray-800 mb-2">1. 平台基本信息</h3>
                            <table class="w-full text-xs">
                                <tr class="border-b">
                                    <td class="py-1.5 text-gray-500 w-40">平台 ID</td>
                                    <td class="py-1.5 font-mono">{{ $integrationDoc['platform_id'] }}</td>
                                </tr>
                                <tr class="border-b">
                                    <td class="py-1.5 text-gray-500">SDK 地址</td>
                                    <td class="py-1.5 font-mono">{{ $integrationDoc['sdk_url'] }}</td>
                                </tr>
                                <tr class="border-b">
                                    <td class="py-1.5 text-gray-500">lkey（完整密钥）</td>
                                    <td class="py-1.5 font-mono">{{ $integrationDoc['lkey_masked'] }}</td>
                                </tr>
                                <tr class="border-b">
                                    <td class="py-1.5 text-gray-500">payKey（完整密钥）</td>
                                    <td class="py-1.5 font-mono">{{ $integrationDoc['pay_key_masked'] }}</td>
                                </tr>
                            </table>
                        </div>

                        {{-- SSO 登录 --}}
                        <div>
                            <h3 class="text-base font-semibold text-gray-800 mb-2">2. SSO 登录接入</h3>
                            <p class="text-xs text-gray-600 mb-2">调用方式：在游戏登录页面接收以下参数并进行签名校验。</p>
                            <p class="text-xs text-gray-600 mb-2">登录地址：<code class="bg-gray-100 px-1.5 py-0.5 rounded font-mono">{{ $integrationDoc['sso_login_url'] ?: '（未配置）' }}</code></p>
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="bg-gray-50 border-b">
                                        <th class="text-left px-2 py-1.5 text-gray-500">参数</th>
                                        <th class="text-left px-2 py-1.5 text-gray-500">类型</th>
                                        <th class="text-center px-2 py-1.5 text-gray-500">必填</th>
                                        <th class="text-left px-2 py-1.5 text-gray-500">说明</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($integrationDoc['login_params'] as $p)
                                        <tr class="border-b border-gray-100">
                                            <td class="px-2 py-1 font-mono">{{ $p[0] }}</td>
                                            <td class="px-2 py-1 text-gray-600">{{ $p[1] }}</td>
                                            <td class="px-2 py-1 text-center">{{ $p[2] === '是' ? '✅' : '' }}</td>
                                            <td class="px-2 py-1 text-gray-600">{{ $p[3] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <p class="text-xs text-gray-500 mt-2"><strong>签名算法：</strong>{{ $integrationDoc['sign_algorithm'] }}</p>
                        </div>

                        {{-- 支付回调 --}}
                        <div>
                            <h3 class="text-base font-semibold text-gray-800 mb-2">3. 支付回调通知</h3>
                            <p class="text-xs text-gray-600 mb-2">回调地址：<code class="bg-gray-100 px-1.5 py-0.5 rounded font-mono">{{ $integrationDoc['pay_notify_url'] ?: '（未配置）' }}</code></p>
                            <p class="text-xs text-gray-600 mb-2">平台在用户支付成功后会向此地址 POST 通知，请验证签名并返回 <code class="bg-gray-100 px-1.5 py-0.5 rounded font-mono">{"status": 0}</code>。</p>
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="bg-gray-50 border-b">
                                        <th class="text-left px-2 py-1.5 text-gray-500">参数</th>
                                        <th class="text-left px-2 py-1.5 text-gray-500">类型</th>
                                        <th class="text-center px-2 py-1.5 text-gray-500">必填</th>
                                        <th class="text-left px-2 py-1.5 text-gray-500">说明</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($integrationDoc['notify_params'] as $p)
                                        <tr class="border-b border-gray-100">
                                            <td class="px-2 py-1 font-mono">{{ $p[0] }}</td>
                                            <td class="px-2 py-1 text-gray-600">{{ $p[1] }}</td>
                                            <td class="px-2 py-1 text-center">{{ $p[2] === '是' ? '✅' : '' }}</td>
                                            <td class="px-2 py-1 text-gray-600">{{ $p[3] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <p class="text-xs text-gray-500 mt-2"><strong>签名算法：</strong>参数排序后 key=value&key=value 拼接，尾部追加 payKey，MD5 小写得到 sign。</p>
                            <p class="text-xs text-gray-500"><strong>返回格式：</strong><code class="bg-gray-100 px-1.5 py-0.5 rounded font-mono">{"status": 0}</code> 表示成功，其他 status 值视为失败。</p>
                        </div>

                        {{-- 角色上报 --}}
                        <div>
                            <h3 class="text-base font-semibold text-gray-800 mb-2">4. 角色上报接口</h3>
                            <p class="text-xs text-gray-600 mb-1">接口地址：<code class="bg-gray-100 px-1.5 py-0.5 rounded font-mono">POST {{ $roleExample['endpoint'] }}</code></p>
                            <p class="text-xs text-gray-600 mb-1">批量接口：<code class="bg-gray-100 px-1.5 py-0.5 rounded font-mono">POST {{ $roleExample['batch_endpoint'] }}</code></p>
                            <p class="text-xs text-gray-600 mb-2">前端 iframe 内通过 postMessage 触发上报，游戏方客户端 JS 调用 <code class="bg-gray-100 px-1.5 py-0.5 rounded font-mono">SDK.reportRole()</code>。</p>
                            <pre class="bg-gray-50 border border-gray-200 rounded p-3 text-xs overflow-x-auto">{{ $roleExample['example_json'] }}</pre>
                        </div>

                        {{-- 开服上报 --}}
                        <div>
                            <h3 class="text-base font-semibold text-gray-800 mb-2">5. 开服上报接口</h3>
                            <p class="text-xs text-gray-600 mb-1">接口地址：<code class="bg-gray-100 px-1.5 py-0.5 rounded font-mono">POST {{ $serverExample['endpoint'] }}</code></p>
                            <p class="text-xs text-gray-600 mb-1">鉴权：使用 lkey 做 MD5 签名</p>
                            <p class="text-xs text-gray-600 mb-2">游戏方在开服时调用此接口，平台自动创建区服记录。</p>
                            <pre class="bg-gray-50 border border-gray-200 rounded p-3 text-xs overflow-x-auto">{{ $serverExample['example_json'] }}</pre>
                        </div>

                        {{-- 返回值规范 --}}
                        <div>
                            <h3 class="text-base font-semibold text-gray-800 mb-2">6. 返回值规范</h3>
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="bg-gray-50 border-b">
                                        <th class="text-left px-2 py-1.5 text-gray-500">接口</th>
                                        <th class="text-left px-2 py-1.5 text-gray-500">成功</th>
                                        <th class="text-left px-2 py-1.5 text-gray-500">失败</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-b border-gray-100">
                                        <td class="px-2 py-1 font-mono">支付回调</td>
                                        <td class="px-2 py-1"><code class="bg-green-50 text-green-700 px-1 rounded">{"status": 0}</code></td>
                                        <td class="px-2 py-1"><code class="bg-red-50 text-red-700 px-1 rounded">{"status": 1, "msg": "..."}</code></td>
                                    </tr>
                                    <tr class="border-b border-gray-100">
                                        <td class="px-2 py-1 font-mono">角色上报</td>
                                        <td class="px-2 py-1"><code class="bg-green-50 text-green-700 px-1 rounded">{"status": 0, "msg": "上报成功"}</code></td>
                                        <td class="px-2 py-1">HTTP 422 验证错误</td>
                                    </tr>
                                    <tr class="border-b border-gray-100">
                                        <td class="px-2 py-1 font-mono">开服上报</td>
                                        <td class="px-2 py-1"><code class="bg-green-50 text-green-700 px-1 rounded">{"errno": 0, "msg": "成功"}</code></td>
                                        <td class="px-2 py-1"><code class="bg-red-50 text-red-700 px-1 rounded">{"errno": 1, "msg": "签名校验失败"}</code></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="py-6 text-center text-gray-400 text-sm">
                        请先选择游戏以生成接入文档
                    </div>
                @endif
            </x-filament::section>
        </div>
    @endif
</x-filament::page>

<script>
function copyDocContent() {
    const doc = document.getElementById('integration-doc');
    if (!doc) return;

    // 提取结构化文本内容
    const sections = doc.querySelectorAll('h3, p, table');
    let text = '游戏接入说明\n';
    text += '==============\n\n';

    sections.forEach(el => {
        if (el.tagName === 'H3') {
            text += '\n' + el.textContent.trim() + '\n';
        } else if (el.tagName === 'P') {
            text += el.textContent.trim() + '\n';
        } else if (el.tagName === 'TABLE') {
            const rows = el.querySelectorAll('tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('th, td');
                const rowText = Array.from(cells).map(c => c.textContent.trim()).join(' | ');
                if (rowText) text += rowText + '\n';
            });
        }
    });

    navigator.clipboard.writeText(text).then(() => {
        // Simple notification
        const btn = event?.target;
        if (btn) {
            btn.textContent = '✅ 已复制';
            setTimeout(() => { btn.innerHTML = '<x-heroicon-o-clipboard class="w-4 h-4" /> 复制全部'; }, 2000);
        }
    });
}
</script>
