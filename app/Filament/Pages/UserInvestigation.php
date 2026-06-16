<?php
namespace App\Filament\Pages;

use App\Models\User;
use App\Models\LoginLog;
use App\Models\RoleReport;
use App\Models\PaymentOrder;
use App\Models\GameNotifyLog;
use App\Models\UserAttribution;
use App\Models\Game;
use App\Filament\Resources\PaymentOrderResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class UserInvestigation extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';
    protected static ?string $navigationGroup = '客服管理';
    protected static ?string $navigationLabel = '用户排查';
    protected static ?string $slug = 'user-investigation';
    protected static ?string $title = '用户排查';
    protected static string $view = 'filament.pages.user-investigation';

    // 搜索关键字
    public string $keyword = '';

    // 搜索状态
    public bool $searched = false;

    // 搜索到的用户（数组形式传递给视图）
    public ?array $user = null;

    // 各数据模块
    public ?array $attribution = null;
    public array $loginLogs = [];
    public array $roleReports = [];
    public array $orders = [];
    public array $failedDeliveries = [];

    // 登录方式映射
    protected array $loginTypeMap = [
        'password' => '密码登录',
        'sms' => '短信登录',
        'oauth' => '第三方登录',
    ];

    // SubmitType 映射
    protected array $submitTypeMap = [
        1 => '创角',
        2 => '升级',
        3 => '进游戏',
        4 => '改名',
    ];

    // 订单状态映射
    protected array $orderStatusColors = [
        'pending' => ['text' => '处理中', 'color' => 'bg-warning-100 text-warning-700'],
        'success' => ['text' => '成功',   'color' => 'bg-success-100 text-success-700'],
        'failed'  => ['text' => '失败',   'color' => 'bg-danger-100 text-danger-700'],
        'closed'  => ['text' => '已关闭', 'color' => 'bg-gray-100 text-gray-600'],
    ];

    // 发货状态映射
    protected array $notifyStatusColors = [
        'pending' => ['text' => '待发货',   'color' => 'bg-warning-100 text-warning-700'],
        'success' => ['text' => '已发货',   'color' => 'bg-success-100 text-success-700'],
        'failed'  => ['text' => '发货失败', 'color' => 'bg-danger-100 text-danger-700'],
    ];

    // 表单 — 搜索框（自动绑定到 $this->keyword）
    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('keyword')
                ->label('')
                ->placeholder('输入用户名、手机号、用户ID、订单号或角色ID')
                ->extraAttributes(['class' => 'max-w-2xl'])
                ->autofocus(),
        ]);
    }

    public function search(): void
    {
        $this->searched = true;
        $this->resetData();

        $keyword = trim($this->keyword);
        if (empty($keyword)) {
            return;
        }

        // 按固定顺序搜索
        $user = null;

        // 1. 按 User.id 精确匹配
        if (is_numeric($keyword)) {
            $user = User::find((int) $keyword);
        }

        // 2. 按 User.mobile 精确匹配
        if (!$user) {
            $user = User::where('mobile', $keyword)->first();
        }

        // 3. 按 User.username 精确匹配
        if (!$user) {
            $user = User::where('username', $keyword)->first();
        }

        // 4. 按 PaymentOrder.order_no 反查
        if (!$user) {
            $order = PaymentOrder::where('order_no', $keyword)->first();
            if ($order) {
                $user = User::find($order->user_id);
            }
        }

        // 5. 按 RoleReport.role_id 反查
        if (!$user) {
            $roleReport = RoleReport::where('role_id', $keyword)->first();
            if ($roleReport) {
                $user = User::find($roleReport->user_id);
            }
        }

        if (!$user) {
            $this->user = null;
            return;
        }

        $this->loadUserData($user);
    }

    protected function resetData(): void
    {
        $this->user = null;
        $this->attribution = null;
        $this->loginLogs = [];
        $this->roleReports = [];
        $this->orders = [];
        $this->failedDeliveries = [];
    }

    protected function loadUserData(User $user): void
    {
        // 用户基础信息
        $this->user = [
            'id' => $user->id,
            'username' => $user->username,
            'masked_mobile' => $user->mobile ? substr($user->mobile, 0, 3) . '****' . substr($user->mobile, -4) : '-',
            'created_at' => $user->created_at?->format('Y-m-d H:i:s') ?? '-',
            'status' => $user->status,
            'last_login_at' => $user->last_login_at?->format('Y-m-d H:i:s') ?? '-',
            'last_login_ip' => $user->last_login_ip ?? '-',
            'real_name_badge' => $this->getRealNameBadge($user),
        ];

        // 注册来源
        $this->loadAttribution($user->id);

        // 登录记录
        $this->loadLoginLogs($user->id);

        // 角色记录
        $this->loadRoleReports($user->id);

        // 订单记录
        $this->loadOrders($user->id);

        // 发货失败订单
        $this->loadFailedDeliveries($user->id);
    }

    protected function getRealNameBadge(User $user): array
    {
        if (!$user->isRealNameVerified()) {
            return ['text' => '未实名', 'color' => 'bg-danger-100 text-danger-700'];
        }
        return $user->isAdult()
            ? ['text' => '已成年', 'color' => 'bg-success-100 text-success-700']
            : ['text' => '未成年', 'color' => 'bg-warning-100 text-warning-700'];
    }

    protected function loadAttribution(int $userId): void
    {
        $attribution = UserAttribution::where('user_id', $userId)
            ->with('promote.game')
            ->first();

        if (!$attribution) {
            $this->attribution = null;
            return;
        }

        $this->attribution = [
            'promote_code' => $attribution->promote_code,
            'promote_name' => $attribution->promote?->promote_name ?? '-',
            'game_name' => $attribution->promote?->game?->name ?? '-',
            'created_at' => $attribution->created_at
                ? (is_string($attribution->created_at) ? $attribution->created_at : $attribution->created_at->format('Y-m-d H:i:s'))
                : '-',
        ];
    }

    protected function loadLoginLogs(int $userId): void
    {
        $logs = LoginLog::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        $this->loginLogs = $logs->map(function ($log) {
            return [
                'created_at' => $log->created_at?->format('Y-m-d H:i:s') ?? '-',
                'ip' => $log->ip ?? '-',
                'login_type' => $log->login_type,
                'login_type_label' => $this->loginTypeMap[$log->login_type] ?? $log->login_type,
                'user_agent' => $log->user_agent ?? '-',
            ];
        })->toArray();
    }

    protected function loadRoleReports(int $userId): void
    {
        // 按 game_id + server_id + role_id 分组聚合，取每组 MAX(created_at) 的最新记录
        // 使用子查询 MAX(id) 实现
        $latestIds = DB::table('role_reports')
            ->select(DB::raw('MAX(id) as id'))
            ->where('user_id', $userId)
            ->groupBy('game_id', 'server_id', 'role_id')
            ->pluck('id');

        $reports = RoleReport::whereIn('id', $latestIds)
            ->with('game')
            ->orderBy('created_at', 'desc')
            ->get();

        $this->roleReports = $reports->map(function ($report) {
            return [
                'game_name' => $report->game?->name ?? '(已删除)',
                'server_name' => $report->server_name ?? '-',
                'role_id' => $report->role_id,
                'role_name' => $report->role_name ?? '-',
                'role_level' => $report->role_level ?? '-',
                'create_time' => $report->create_time?->format('Y-m-d H:i:s') ?? '-',
                'last_report_at' => $report->created_at?->format('Y-m-d H:i:s') ?? '-',
                'submit_type_label' => $this->submitTypeMap[$report->submit_type] ?? "类型{$report->submit_type}",
            ];
        })->toArray();
    }

    protected function loadOrders(int $userId): void
    {
        $orders = PaymentOrder::where('user_id', $userId)
            ->with('game')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $this->orders = $orders->map(function ($order) {
            $statusStyle = $this->orderStatusColors[$order->status] ?? ['text' => $order->status, 'color' => 'bg-gray-100 text-gray-600'];
            $notifyStyle = $order->notify_status
                ? ($this->notifyStatusColors[$order->notify_status] ?? ['text' => $order->notify_status, 'color' => 'bg-gray-100 text-gray-600'])
                : ['text' => '无需发货', 'color' => 'bg-gray-100 text-gray-500'];

            return [
                'order_no' => $order->order_no,
                'detail_url' => PaymentOrderResource::getUrl('view', ['record' => $order]),
                'reconciliation_url' => '/admin/order-reconciliation',
                'game_name' => $order->game?->name ?? '-',
                'server_id' => $order->server_id ?? '-',
                'role_name' => $order->role_name ?? '-',
                'amount' => number_format((float) $order->amount, 2),
                'status_badge' => $statusStyle,
                'paid_at' => $order->paid_at?->format('Y-m-d H:i:s') ?? '-',
                'notify_badge' => $notifyStyle,
            ];
        })->toArray();
    }

    protected function loadFailedDeliveries(int $userId): void
    {
        $failedOrders = PaymentOrder::where('user_id', $userId)
            ->where('notify_status', 'failed')
            ->with('game')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // 批量查询最新的发货失败日志，避免 N+1
        $orderIds = $failedOrders->pluck('id');
        $latestLogIds = GameNotifyLog::whereIn('payment_order_id', $orderIds)
            ->select(DB::raw('MAX(id) as id'))
            ->groupBy('payment_order_id')
            ->pluck('id');
        $latestLogs = GameNotifyLog::whereIn('id', $latestLogIds)
            ->get()
            ->keyBy('payment_order_id');

        $this->failedDeliveries = $failedOrders->map(function ($order) use ($latestLogs) {
            $lastLog = $latestLogs->get($order->id);

            return [
                'order_no' => $order->order_no,
                'detail_url' => PaymentOrderResource::getUrl('view', ['record' => $order]),
                'reconciliation_url' => '/admin/order-reconciliation',
                'amount' => number_format((float) $order->amount, 2),
                'error_message' => $lastLog?->error_message ?? '未知错误',
                'response_body' => $lastLog?->response_body ?? '-',
            ];
        })->toArray();
    }
}
