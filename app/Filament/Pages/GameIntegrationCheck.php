<?php
namespace App\Filament\Pages;

use App\Models\Game;
use App\Models\GameNotifyLog;
use App\Models\GameSsoConfig;
use App\Models\User;
use App\Services\SsoService;
use App\Services\GamePayService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;

class GameIntegrationCheck extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationGroup = '游戏管理';
    protected static ?string $navigationLabel = '接入自检';
    protected static ?string $title = '游戏接入自检';
    protected static string $view = 'filament.pages.game-integration-check';

    public ?int $gameId = null;
    public ?int $testUserId = null;
    public ?int $testServerId = null;
    public ?array $ssoResult = null;
    public ?array $notifyTestResult = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('gameId')
                ->label('选择游戏')
                ->placeholder('选择要测试的游戏')
                ->options(Game::active()->pluck('name', 'id'))
                ->reactive()
                ->required(),
            TextInput::make('testUserId')
                ->label('测试用户ID')
                ->default(1)
                ->numeric()
                ->required(),
            TextInput::make('testServerId')
                ->label('测试区服ID')
                ->default(1)
                ->numeric()
                ->required(),
        ]);
    }

    public function testSso(): void
    {
        $this->validate();

        $config = GameSsoConfig::where('game_id', $this->gameId)->where('enabled', true)->first();
        if (!$config) {
            Notification::make()->danger()->title('SSO配置不存在或未启用')->send();
            return;
        }

        $user = User::find($this->testUserId);
        if (!$user) {
            Notification::make()->danger()->title('用户不存在')->send();
            return;
        }

        try {
            $ssoService = app(SsoService::class);
            $params = $ssoService->generateLoginParams($user, $this->gameId, $this->testServerId);
            $fullUrl = $ssoService->buildLoginUrl($params);

            $this->ssoResult = [
                'url' => $fullUrl,
                'params' => $params,
                'config' => [
                    'login_url' => $config->login_url,
                    'platform_id' => $config->platform_id,
                    'login_key' => substr($config->login_key, 0, 8) . '****',
                ],
            ];

            Notification::make()->success()->title('SSO参数生成成功')->send();
        } catch (\Exception $e) {
            Notification::make()->danger()->title('SSO生成失败')->body($e->getMessage())->send();
        }
    }

    public function testNotify(): void
    {
        $this->validate();

        $config = GameSsoConfig::where('game_id', $this->gameId)->where('enabled', true)->first();
        if (!$config || !$config->pay_notify_url) {
            Notification::make()->danger()->title('支付通知URL未配置')->send();
            return;
        }

        try {
            $response = Http::timeout(10)->get($config->pay_notify_url);
            $this->notifyTestResult = [
                'url' => $config->pay_notify_url,
                'http_code' => $response->status(),
                'headers' => $response->headers(),
                'body_preview' => mb_substr($response->body(), 0, 1000),
                'success' => $response->successful(),
            ];

            if ($response->successful()) {
                Notification::make()->success()->title('通知地址可达')->body("HTTP {$response->status()}")->send();
            } else {
                Notification::make()->warning()->title('通知地址返回异常')->body("HTTP {$response->status()}")->send();
            }
        } catch (\Exception $e) {
            $this->notifyTestResult = [
                'url' => $config->pay_notify_url,
                'error' => $e->getMessage(),
                'success' => false,
            ];
            Notification::make()->danger()->title('通知地址不可达')->body($e->getMessage())->send();
        }
    }

    public function getRecentLogs(): array
    {
        if (!$this->gameId) {
            return [];
        }

        return GameNotifyLog::with('paymentOrder')
            ->whereHas('paymentOrder', function ($q) {
                $q->where('game_id', $this->gameId);
            })
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get()
            ->toArray();
    }
}
