<?php
namespace App\Filament\Pages;

use App\Models\Game;
use App\Models\GameNotifyLog;
use App\Models\GameServer;
use App\Models\GameSsoConfig;
use App\Models\PaymentOrder;
use App\Models\RoleReport;
use App\Models\ServerOpenReport;
use App\Models\User;
use App\Services\GamePayService;
use App\Services\SsoService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class IntegrationSandbox extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationGroup = '游戏管理';
    protected static ?int $navigationSort = 30;
    protected static ?string $navigationLabel = '接入沙箱';
    protected static ?string $title = '接入沙箱';
    protected static string $view = 'filament.pages.integration-sandbox';

    // 当前 Tab
    public string $activeTab = 'overview';

    // 公用 - 游戏选择（Tab 1/5 不需要选择，2/3/4 需要）
    public ?int $gameId = null;

    // Tab2: SSO 调试
    public ?int $ssoUserId = null;
    public ?int $ssoServerId = null;
    public ?array $ssoResult = null;
    public ?array $ssoDebugInfo = null;

    // Tab3: 支付发货模拟
    public ?int $payUserId = null;
    public ?float $payAmount = null;
    public ?string $payRoleId = null;
    public ?string $payProductId = null;
    public ?array $payResult = null;

    // 数据缓存
    public array $overviewData = [];
    public array $roleReports = [];
    public array $serverOpenReports = [];
    public array $notifyLogs = [];

    // 订货量映射
    protected array $submitTypeMap = [1 => '创角', 2 => '升级', 3 => '进游戏', 4 => '改名'];

    public function mount(): void
    {
        $this->loadOverviewData();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('gameId')
                ->label('选择游戏')
                ->placeholder('选择游戏')
                ->options(Game::active()->pluck('name', 'id'))
                ->reactive()
                ->afterStateUpdated(function () {
                    $this->ssoResult = null;
                    $this->ssoDebugInfo = null;
                    $this->payResult = null;
                    $this->roleReports = [];
                    $this->serverOpenReports = [];
                    $this->notifyLogs = [];
                }),
        ]);
    }

    // ==================== Tab 切换 ====================

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        match ($tab) {
            'overview' => $this->loadOverviewData(),
            'role' => $this->loadRoleReports(),
            'server' => $this->loadServerOpenReports(),
            default => null,
        };
    }

    // ==================== Tab1: 接入概览 ====================

    public function loadOverviewData(): void
    {
        $games = Game::active()->with('ssoConfig')->orderBy('name')->get();
        $this->overviewData = $games->map(function (Game $game) {
            $config = $game->ssoConfig;

            // 最近发货日志状态
            $lastNotifyLog = $config
                ? GameNotifyLog::where('game_id', $game->id)
                    ->orderByDesc('created_at')
                    ->first()
                : null;

            // 最近角色上报时间
            $lastRoleReport = RoleReport::where('game_id', $game->id)
                ->orderByDesc('created_at')
                ->first();

            // 最近开服上报时间
            $lastServerOpen = ServerOpenReport::where('game_id', $game->id)
                ->orderByDesc('created_at')
                ->first();

            return [
                'id' => $game->id,
                'name' => $game->name,
                'has_config' => (bool) $config,
                'has_login_url' => $config && !empty($config->login_url),
                'has_notify_url' => $config && !empty($config->pay_notify_url),
                'has_lkey' => $config && !empty($config->login_key),
                'has_pay_key' => $config && !empty($config->pay_key),
                'enabled' => $config?->enabled ?? false,
                'last_notify_status' => $lastNotifyLog?->status,
                'last_notify_time' => $lastNotifyLog?->created_at?->diffForHumans(),
                'last_role_report_time' => $lastRoleReport?->created_at?->diffForHumans(),
                'last_server_open_time' => $lastServerOpen?->created_at?->diffForHumans(),
            ];
        })->toArray();
    }

    // ==================== Tab2: SSO 调试 ====================

    public function runSsoTest(): void
    {
        $this->validateGameSelected();

        $config = GameSsoConfig::where('game_id', $this->gameId)->first();
        if (!$config) {
            Notification::make()->danger()->title('该游戏未配置 SSO')->send();
            return;
        }

        $userId = $this->ssoUserId ?? 1;
        $serverId = $this->ssoServerId ?? 1;

        $user = User::find($userId);
        if (!$user) {
            Notification::make()->danger()->title("用户 ID {$userId} 不存在")->send();
            return;
        }

        try {
            $ssoService = app(SsoService::class);
            $params = $ssoService->generateLoginParams($user, $this->gameId, $serverId);
            $fullUrl = $ssoService->buildLoginUrl($params);

            // 原始参数（排序前）
            $rawParams = [
                'uid' => (string)$user->id,
                'platform' => (string)$config->platform_id,
                'serverid' => (string)$serverId,
                'logintime' => (string)$params['logintime'],
                'is_adult' => (string)$params['is_adult'],
                'game_id' => (string)$this->gameId,
                'client' => '1',
            ];
            ksort($rawParams);
            $queryString = http_build_query($rawParams);
            $signedString = $queryString . $config->login_key;

            $this->ssoResult = [
                'url' => $fullUrl,
                'params' => $params,
                'token' => $params['token'],
            ];

            $this->ssoDebugInfo = [
                'login_url' => $config->login_url,
                'platform_id' => $config->platform_id,
                'lkey_masked' => substr($config->login_key, 0, 6) . '****' . substr($config->login_key, -4),
                'login_key_full' => $config->login_key,
                'raw_params' => $rawParams,
                'sorted_params' => $rawParams,
                'query_string' => $queryString,
                'signed_string' => $signedString,
                'md5_input' => $queryString . '[lkey]',
                'md5_result' => $params['token'],
                'iframe_url' => $fullUrl,
                'user_id' => $user->id,
                'username' => $user->username,
                'server_id' => $serverId,
            ];

            Notification::make()->success()->title('SSO URL 生成成功')->send();
        } catch (\Exception $e) {
            Notification::make()->danger()->title('SSO 生成失败')->body($e->getMessage())->send();
        }
    }

    // ==================== Tab3: 支付发货模拟 ====================

    public function runPaySimulation(): void
    {
        $this->validateGameSelected();

        $config = GameSsoConfig::where('game_id', $this->gameId)->first();
        if (!$config || !$config->pay_key) {
            Notification::make()->danger()->title('该游戏未配置 payKey，无法模拟发货')->send();
            return;
        }
        if (!$config->pay_notify_url) {
            Notification::make()->danger()->title('该游戏未配置支付回调地址')->send();
            return;
        }

        if (!$this->payUserId || !$this->payAmount) {
            Notification::make()->danger()->title('请填写用户ID和金额')->send();
            return;
        }

        $user = User::find($this->payUserId);
        if (!$user) {
            Notification::make()->danger()->title("用户 ID {$this->payUserId} 不存在")->send();
            return;
        }

        if ($this->payAmount <= 0 || $this->payAmount > 999999) {
            Notification::make()->danger()->title('金额不合法（0-999999）')->send();
            return;
        }

        DB::beginTransaction();
        try {
            // 创建一笔支付成功的测试订单
            $orderNo = 'TEST' . now()->format('YmdHis') . mt_rand(1000, 9999);
            $order = PaymentOrder::create([
                'order_no' => $orderNo,
                'user_id' => $this->payUserId,
                'game_id' => $this->gameId,
                'server_id' => $this->ssoServerId ?? 1,
                'amount' => $this->payAmount,
                'status' => 'success',
                'paid_at' => now(),
                'role_id' => $this->payRoleId ?? '',
                'product_id' => $this->payProductId ?? 'test_product',
                'product_name' => '测试商品',
                'notify_status' => 'pending',
                'notify_times' => 0,
            ]);

            // 生成发货签名（与 GamePayService 逻辑一致）
            $notifyParams = [
                'uid' => (string)$order->user_id,
                'serverId' => (string)$order->server_id,
                'orderId' => $order->order_no,
                'money' => (string)$order->amount,
                'goodsId' => $order->product_id ?? '',
                'time' => (string)time(),
                'rid' => $order->role_id ?? '',
                'ext' => $order->ext ?? '',
            ];
            ksort($notifyParams);
            $sign = strtolower(md5(http_build_query($notifyParams) . $config->pay_key));
            $notifyParams['sign'] = $sign;

            $notifyUrl = $config->pay_notify_url;

            // 调用发货接口
            $gamePayService = app(GamePayService::class);
            $result = $gamePayService->notifyGameServer($order);

            // 获取刚写入的发货日志
            $notifyLog = GameNotifyLog::where('payment_order_id', $order->id)
                ->orderByDesc('created_at')
                ->first();

            $this->payResult = [
                'order_no' => $orderNo,
                'order_id' => $order->id,
                'test_order' => true,
                'notify_url' => $notifyUrl,
                'notify_params' => $notifyParams,
                'sign' => $sign,
                'signed_string' => http_build_query($notifyParams) . '[payKey]',
                'pay_key_masked' => substr($config->pay_key, 0, 6) . '****' . substr($config->pay_key, -4),
                'pay_key_full' => $config->pay_key,
                'http_code' => $notifyLog?->http_code,
                'response_body' => $notifyLog?->response_body ?? '-',
                'error_message' => $notifyLog?->error_message ?? null,
                'success' => $result,
            ];

            DB::commit();

            Notification::make()
                ->title($result ? '发货模拟成功' : '发货模拟完成（游戏方返回异常）')
                ->body("订单号: {$orderNo}")
                ->send();
        } catch (\Exception $e) {
            DB::rollBack();
            Notification::make()->danger()->title('模拟失败')->body($e->getMessage())->send();
        }
    }

    // ==================== Tab4: 角色上报 ====================

    public function loadRoleReports(): void
    {
        if (!$this->gameId) {
            $this->roleReports = [];
            return;
        }

        $reports = RoleReport::where('game_id', $this->gameId)
            ->with('game')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $this->roleReports = $reports->map(function ($r) {
            return [
                'id' => $r->id,
                'user_id' => $r->user_id,
                'server_id' => $r->server_id,
                'server_name' => $r->server_name ?? '-',
                'role_id' => $r->role_id,
                'role_name' => $r->role_name ?? '-',
                'role_level' => $r->role_level ?? '-',
                'submit_type' => $r->submit_type,
                'submit_type_label' => $this->submitTypeMap[$r->submit_type] ?? "类型{$r->submit_type}",
                'create_time' => $r->create_time?->format('Y-m-d H:i:s') ?? '-',
                'reported_at' => $r->created_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    public function getRoleReportExample(): array
    {
        return [
            'endpoint' => 'POST /api/game/role',
            'auth' => '需登录态（session）',
            'content_type' => 'application/x-www-form-urlencoded 或 JSON',
            'params' => [
                'game_id' => ['type' => 'integer', 'required' => true, 'desc' => '游戏ID'],
                'submit_type' => ['type' => 'integer', 'required' => true, 'desc' => '1=创角 2=升级 3=进游戏 4=改名'],
                'server_id' => ['type' => 'integer', 'required' => true, 'desc' => '区服ID'],
                'server_name' => ['type' => 'string', 'required' => false, 'desc' => '区服名称'],
                'role_id' => ['type' => 'string', 'required' => true, 'desc' => '角色ID'],
                'role_name' => ['type' => 'string', 'required' => false, 'desc' => '角色名称'],
                'role_level' => ['type' => 'integer', 'required' => false, 'desc' => '角色等级'],
                'zone_id' => ['type' => 'integer', 'required' => false, 'desc' => '战区ID'],
                'zone_name' => ['type' => 'string', 'required' => false, 'desc' => '战区名称'],
                'create_time' => ['type' => 'integer', 'required' => false, 'desc' => '角色创建时间戳'],
            ],
            'example_json' => json_encode([
                'game_id' => 1,
                'submit_type' => 1,
                'server_id' => 1001,
                'server_name' => '第1服',
                'role_id' => 'R10001',
                'role_name' => '剑侠客',
                'role_level' => 50,
                'create_time' => 1718524800,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'response_success' => json_encode(['status' => 0, 'msg' => '上报成功'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'batch_endpoint' => 'POST /api/game/roles',
            'batch_example' => json_encode([
                ['game_id' => 1, 'submit_type' => 1, 'server_id' => 1001, 'role_id' => 'R10001', 'role_level' => 50],
                ['game_id' => 1, 'submit_type' => 2, 'server_id' => 1001, 'role_id' => 'R10001', 'role_level' => 55],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'batch_response' => json_encode(['status' => 0, 'msg' => '批量上报成功', 'count' => 2], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ];
    }

    // ==================== Tab5: 开服上报 + 文档 ====================

    public function loadServerOpenReports(): void
    {
        if (!$this->gameId) {
            $this->serverOpenReports = [];
            return;
        }

        $reports = ServerOpenReport::where('game_id', $this->gameId)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $this->serverOpenReports = $reports->map(function ($r) {
            return [
                'id' => $r->id,
                'project' => $r->project,
                'open_server' => $r->open_server,
                'open_server_time' => $r->open_server_time?->format('Y-m-d H:i:s') ?? '-',
                'created_role_num' => $r->created_role_num ?? 0,
                'pay_num' => $r->pay_num ?? 0,
                'reported_at' => $r->created_at->format('Y-m-d H:i:s'),
            ];
        })->toArray();
    }

    public function getServerOpenExample(): array
    {
        return [
            'endpoint' => 'POST /api/server/auto-open',
            'auth' => '签名验证（使用 lkey）',
            'content_type' => 'application/x-www-form-urlencoded',
            'params' => [
                'project' => ['type' => 'string', 'required' => true, 'desc' => '平台ID（platform_id）'],
                'open_server' => ['type' => 'integer', 'required' => true, 'desc' => '开服编号（第N服）'],
                'open_server_time' => ['type' => 'datetime', 'required' => false, 'desc' => '开服时间，默认当前时间'],
                'sign' => ['type' => 'string', 'required' => true, 'desc' => 'MD5签名，排序后拼接lkey'],
            ],
            'sign_algorithm' => '1. 除 sign 外所有参数按 key 升序排列。2. 拼接为 key=value&key=value 格式。3. 尾部追加 lkey。4. MD5 小写。',
            'example_json' => json_encode([
                'project' => 'P001',
                'open_server' => 100,
                'open_server_time' => '2026-06-16 10:00:00',
                'sign' => strtolower(md5('open_server=100&open_server_time=2026-06-16%2010%3A00%3A00&project=P001' . '[lkey]')),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'response_success' => json_encode(['errno' => 0, 'msg' => '成功', 'data' => []], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'response_fail' => json_encode(['errno' => 1, 'msg' => '签名校验失败'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ];
    }

    // ==================== 接入文档生成 ====================

    public function getIntegrationDoc(): ?array
    {
        if (!$this->gameId) {
            return null;
        }

        $game = Game::with('ssoConfig')->find($this->gameId);
        if (!$game || !$game->ssoConfig) {
            return null;
        }

        $config = $game->ssoConfig;
        $loginParams = [
            ['uid', 'int', '是', '玩家UID'],
            ['platform', 'string', '是', '平台ID'],
            ['serverid', 'int', '是', '区服ID'],
            ['logintime', 'int', '是', '当前时间戳'],
            ['is_adult', 'int', '是', '0=未实名 1=已成年 2=未成年'],
            ['game_id', 'int', '是', '游戏ID'],
            ['client', 'int', '是', '固定 1'],
            ['token', 'string', '是', 'MD5 签名'],
        ];

        $notifyParams = [
            ['uid', 'string', '是', '玩家UID'],
            ['serverId', 'string', '是', '区服ID'],
            ['orderId', 'string', '是', '平台订单号'],
            ['money', 'string', '是', '充值金额（元）'],
            ['goodsId', 'string', '是', '商品ID'],
            ['time', 'string', '是', '通知时间戳'],
            ['rid', 'string', '否', '角色ID'],
            ['ext', 'string', '否', '扩展参数'],
            ['sign', 'string', '是', 'MD5 签名'],
        ];

        return [
            'game_name' => $game->name,
            'platform_id' => $config->platform_id,
            'sso_login_url' => $config->login_url,
            'pay_notify_url' => $config->pay_notify_url,
            'sdk_url' => url('/sdk/sdk-init.js'),
            'login_params' => $loginParams,
            'notify_params' => $notifyParams,
            'lkey_masked' => $config->login_key ? substr($config->login_key, 0, 6) . '****' : '(未配置)',
            'pay_key_masked' => $config->pay_key ? substr($config->pay_key, 0, 6) . '****' : '(未配置)',
            'sign_algorithm' => '1. 除 sign 外所有参数按 key 升序排列。2. 拼接为 key=value&key=value。3. 尾部追加 lkey（SSO签名）或 payKey（发货签名）。4. MD5 小写。',
        ];
    }

    // ==================== 通用方法 ====================

    protected function validateGameSelected(): void
    {
        if (!$this->gameId) {
            Notification::make()->danger()->title('请先选择游戏')->send();
            return;
        }
    }

    public function getRecentNotifyLogs(): array
    {
        if (!$this->gameId) {
            return [];
        }

        return GameNotifyLog::with('paymentOrder')
            ->whereHas('paymentOrder', fn($q) => $q->where('game_id', $this->gameId))
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->toArray();
    }

    public function getGameServers(): array
    {
        if (!$this->gameId) {
            return [];
        }
        return GameServer::where('game_id', $this->gameId)
            ->orderBy('server_id')
            ->get(['id', 'name', 'server_id'])
            ->toArray();
    }
}
