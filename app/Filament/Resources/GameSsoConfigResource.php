<?php
namespace App\Filament\Resources;

use App\Filament\Resources\GameSsoConfigResource\Pages;
use App\Models\GameSsoConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GameSsoConfigResource extends Resource
{
    protected static ?string $model = GameSsoConfig::class;
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationGroup = '游戏管理';
    protected static ?string $navigationLabel = '游戏接入配置';
    protected static ?string $modelLabel = '接入配置';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('game_id')
                ->relationship('game', 'name')
                ->required()
                ->unique(ignoreRecord: true)
                ->label('游戏'),
            Forms\Components\TextInput::make('platform_id')
                ->required()
                ->label('平台ID(游戏方提供)'),
            Forms\Components\TextInput::make('login_url')
                ->required()
                ->url()
                ->label('游戏登录接口地址'),
            Forms\Components\TextInput::make('login_key')
                ->required()
                ->password()
                ->revealable()
                ->default(fn() => bin2hex(random_bytes(16)))
                ->label('登录密钥 lkey'),
            Forms\Components\TextInput::make('pay_key')
                ->required()
                ->password()
                ->revealable()
                ->default(fn() => bin2hex(random_bytes(16)))
                ->label('支付密钥 payKey'),
            Forms\Components\TextInput::make('pay_notify_url')
                ->url()
                ->label('支付回调通知地址(游戏方发货接口)'),
            Forms\Components\TextInput::make('server_open_url')
                ->url()
                ->label('开服通知地址'),
            Forms\Components\Toggle::make('enabled')
                ->default(true)
                ->label('启用'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('game.name')->label('游戏'),
            Tables\Columns\TextColumn::make('platform_id')->label('平台ID'),
            Tables\Columns\TextColumn::make('login_url')->limit(30)->label('登录地址'),
            Tables\Columns\IconColumn::make('enabled')->boolean()->label('状态'),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\Action::make('test_login')
                ->label('测试登录')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->url(fn(\App\Models\GameSsoConfig $record): string => $record->login_url)
                ->openUrlInNewTab(),
            Tables\Actions\Action::make('test_notify')
                ->label('测试发货')
                ->icon('heroicon-o-bolt')
                ->color('warning')
                ->action(function (\App\Models\GameSsoConfig $record): void {
                    try {
                        $order = \App\Models\PaymentOrder::where('game_id', $record->game_id)
                            ->where('status', 'success')
                            ->latest()
                            ->first();
                        if (!$order) {
                            \Filament\Notifications\Notification::make()
                                ->warning()->title('没有已支付的订单用于测试')->send();
                            return;
                        }
                        $result = app(\App\Services\GamePayService::class)->resetNotifyStatus($order);
                        if ($result) {
                            \Filament\Notifications\Notification::make()->success()->title('测试发货成功')->send();
                        } else {
                            \Filament\Notifications\Notification::make()->warning()->title('测试发货失败')->body('请检查回调地址和payKey')->send();
                        }
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()->danger()->title('异常')->body($e->getMessage())->send();
                    }
                }),
        ]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGameSsoConfigs::route('/'),
            'create' => Pages\CreateGameSsoConfig::route('/create'),
            'edit' => Pages\EditGameSsoConfig::route('/{record}/edit'),
        ];
    }
}
