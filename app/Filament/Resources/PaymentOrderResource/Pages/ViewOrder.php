<?php
namespace App\Filament\Resources\PaymentOrderResource\Pages;

use App\Filament\Resources\PaymentOrderResource;
use App\Models\PaymentFlow;
use App\Services\GamePayService;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = PaymentOrderResource::class;

    public function getTitle(): string
    {
        return "订单详情 - {$this->record->order_no}";
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        // 手动补单：将 pending/failed 的订单强制标记为成功
        if (in_array($this->record->status, ['pending', 'failed'])) {
            $actions[] = Actions\Action::make('manual_top_up')
                ->label('手动补单')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->form([
                    TextInput::make('paid_amount')
                        ->label('实付金额')
                        ->numeric()
                        ->default($this->record->amount)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'status' => 'success',
                        'paid_at' => now(),
                        'amount' => $data['paid_amount'],
                    ]);
                    Notification::make()->success()->title('补单成功')->send();
                });
        }

        // 重试发货
        if ($this->record->status === 'success' && $this->record->notify_status !== 'success') {
            $actions[] = Actions\Action::make('retry_notify_detail')
                ->label('重试发货')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action(function (): void {
                    $result = app(GamePayService::class)->notifyGameServer($this->record);
                    if ($result) {
                        Notification::make()->success()->title('发货成功')->send();
                    } else {
                        Notification::make()->warning()->title('发货失败')->body('请检查游戏方配置')->send();
                    }
                    $this->refresh();
                });
        }

        return $actions;
    }

    public function getInfolist(?string $name = null): \Filament\Infolists\Infolist
    {
        $record = $this->record;

        return parent::getInfolist()
            ->schema([
                // === 订单基本信息 ===
                \Filament\Infolists\Components\Section::make('订单信息')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('order_no')->label('订单号')->copyable(),
                        \Filament\Infolists\Components\TextEntry::make('user.username')->label('用户'),
                        \Filament\Infolists\Components\TextEntry::make('amount')->label('金额')->money('CNY'),
                        \Filament\Infolists\Components\TextEntry::make('status')
                            ->label('状态')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'success' => 'success', 'pending' => 'warning', 'failed' => 'danger', default => 'gray',
                            })
                            ->formatStateUsing(fn(string $state): string => match ($state) {
                                'success' => '成功', 'pending' => '处理中', 'failed' => '失败', default => $state,
                            }),
                        \Filament\Infolists\Components\TextEntry::make('paid_at')->label('支付时间')->dateTime(),
                        \Filament\Infolists\Components\TextEntry::make('created_at')->label('创建时间')->dateTime(),
                    ])->columns(2),

                // === 游戏信息 ===
                \Filament\Infolists\Components\Section::make('游戏信息')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('game.name')->label('游戏'),
                        \Filament\Infolists\Components\TextEntry::make('server_id')->label('区服ID'),
                        \Filament\Infolists\Components\TextEntry::make('role_id')->label('角色ID'),
                        \Filament\Infolists\Components\TextEntry::make('role_name')->label('角色名'),
                        \Filament\Infolists\Components\TextEntry::make('product_id')->label('商品ID'),
                        \Filament\Infolists\Components\TextEntry::make('product_name')->label('商品名'),
                        \Filament\Infolists\Components\TextEntry::make('product_desc')->label('商品描述'),
                        \Filament\Infolists\Components\TextEntry::make('ext')->label('透传参数'),
                    ])->columns(3)->visible(fn() => $record->game_id !== null),

                // === 发货状态 ===
                \Filament\Infolists\Components\Section::make('发货状态')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('notify_status')
                            ->label('发货状态')
                            ->badge()
                            ->color(fn(?string $state): string => match ($state) {
                                'success' => 'success', 'pending' => 'warning', 'failed' => 'danger', default => 'gray',
                            })
                            ->formatStateUsing(fn(?string $state): string => match ($state) {
                                'success' => '已发货', 'pending' => '待发货', 'failed' => '发货失败', default => '无需发货',
                            }),
                        \Filament\Infolists\Components\TextEntry::make('notify_times')->label('重试次数'),
                        \Filament\Infolists\Components\TextEntry::make('last_notify_at')->label('最后重试时间')->dateTime(),
                    ])->columns(3),

                // === 支付流水 ===
                \Filament\Infolists\Components\Section::make('支付流水 (Payment Flows)')
                    ->schema([
                        \Filament\Infolists\Components\RepeatableEntry::make('flows')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('channel')
                                    ->label('渠道')
                                    ->formatStateUsing(fn(string $state): string => match ($state) {
                                        'alipay' => '支付宝', 'wechat' => '微信支付', default => $state,
                                    }),
                                \Filament\Infolists\Components\TextEntry::make('channel_order_no')->label('渠道订单号')->copyable(),
                                \Filament\Infolists\Components\TextEntry::make('status')
                                    ->label('状态')
                                    ->badge()
                                    ->color(fn(?string $state): string => $state === 'success' ? 'success' : 'gray'),
                                \Filament\Infolists\Components\TextEntry::make('created_at')->label('时间')->dateTime(),
                            ])->columns(4),
                    ])->visible(fn() => $record->flows()->count() > 0),

                // === 渠道回调原始数据 ===
                \Filament\Infolists\Components\Section::make('渠道回调原始数据')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('flows.0.channel_data')
                            ->label('最近一次回调数据')
                            ->formatStateUsing(fn(mixed $state): string => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                            ->monospace()
                            ->copyable(),
                    ])->visible(fn() => $record->flows()->count() > 0),

                // === 发货日志 ===
                \Filament\Infolists\Components\Section::make('发货日志 (Game Notify Logs)')
                    ->schema([
                        \Filament\Infolists\Components\RepeatableEntry::make('gameNotifyLogs')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('created_at')->label('时间')->dateTime(),
                                \Filament\Infolists\Components\TextEntry::make('status')
                                    ->label('结果')
                                    ->badge()
                                    ->color(fn(string $state): string => $state === 'success' ? 'success' : 'danger')
                                    ->formatStateUsing(fn(string $state): string => $state === 'success' ? '成功' : '失败'),
                                \Filament\Infolists\Components\TextEntry::make('http_code')->label('HTTP状态码'),
                                \Filament\Infolists\Components\TextEntry::make('response_body')
                                    ->label('响应内容')
                                    ->limit(200)
                                    ->copyable(),
                                \Filament\Infolists\Components\TextEntry::make('error_message')
                                    ->label('错误信息')
                                    ->visible(fn(?string $state): bool => filled($state)),
                            ])->columns(4)
                            ->defaultSort('created_at', 'desc'),
                    ])->visible(fn(\App\Models\PaymentOrder $record): bool => $record->gameNotifyLogs()->count() > 0),
            ]);
    }
}
