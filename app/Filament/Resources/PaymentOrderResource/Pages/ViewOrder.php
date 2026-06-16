<?php
namespace App\Filament\Resources\PaymentOrderResource\Pages;

use App\Filament\Resources\PaymentOrderResource;
use App\Models\AdminAuditLog;
use App\Models\PaymentOperationLog;
use App\Enums\NotifyStatus;
use App\Enums\PaymentFlowStatus;
use App\Enums\PaymentOrderStatus;
use App\Services\GamePayService;
use App\Services\PaymentService;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
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
        if (in_array($this->record->status, [PaymentOrderStatus::PENDING, PaymentOrderStatus::FAILED])) {
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
                    Textarea::make('remark')
                        ->label('操作备注')
                        ->placeholder('补单原因'),
                ])
                ->action(function (array $data): void {
                    $oldAmount = $this->record->amount;
                    $this->record->update([
                        'status' => PaymentOrderStatus::SUCCESS,
                        'paid_at' => now(),
                        'amount' => $data['paid_amount'],
                    ]);
                    PaymentOperationLog::log($this->record->id, 'top_up',
                        $data['remark'] ?? null,
                        ['old_amount' => $oldAmount, 'new_amount' => $data['paid_amount']]
                    );
                    AdminAuditLog::record('top_up', 'payment_order', (string)$this->record->id,
                        ['status' => PaymentOrderStatus::PENDING, 'amount' => $oldAmount],
                        ['status' => PaymentOrderStatus::SUCCESS, 'amount' => $data['paid_amount']],
                        $data['remark'] ?? null
                    );
                    Notification::make()->success()->title('补单成功')->send();
                });
        }

        // 重试发货
        if ($this->record->status === PaymentOrderStatus::SUCCESS && $this->record->notify_status !== NotifyStatus::SUCCESS) {
            $actions[] = Actions\Action::make('retry_notify_detail')
                ->label('重试发货')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->form([
                    Textarea::make('remark')
                        ->label('操作备注'),
                ])
                ->action(function (array $data): void {
                    $result = app(GamePayService::class)->notifyGameServer($this->record);
                    PaymentOperationLog::log($this->record->id, 'retry_notify',
                        $data['remark'] ?? null,
                        ['result' => $result]
                    );
                    AdminAuditLog::record('retry_notify', 'payment_order', (string)$this->record->id,
                        ['notify_status' => $this->record->notify_status],
                        ['notify_status' => $result ? NotifyStatus::SUCCESS : ($this->record->notify_times >= 3 ? NotifyStatus::FAILED : NotifyStatus::PENDING)],
                        $data['remark'] ?? null
                    );
                    if ($result) {
                        Notification::make()->success()->title('发货成功')->send();
                    } else {
                        Notification::make()->warning()->title('发货失败')->body('请检查游戏方配置')->send();
                    }
                    $this->refresh();
                });
        }

        // 退款（仅成功订单可退）
        if ($this->record->status === PaymentOrderStatus::SUCCESS) {
            $actions[] = Actions\Action::make('refund')
                ->label('退款')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->requiresConfirmation()
                ->form([
                    Textarea::make('remark')
                        ->label('退款原因')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $result = app(PaymentService::class)->refund($this->record);
                    if ($result['code'] === 0) {
                        PaymentOperationLog::log($this->record->id, 'refund',
                            $data['remark'] ?? null,
                            ['refund_result' => $result]
                        );
                        AdminAuditLog::record('refund', 'payment_order', (string)$this->record->id,
                            ['status' => PaymentOrderStatus::SUCCESS, 'amount' => $this->record->amount],
                            ['status' => PaymentOrderStatus::CLOSED, 'amount' => $this->record->amount],
                            $data['remark'] ?? null
                        );
                        Notification::make()->success()->title($result['msg'])->send();
                    } else {
                        AdminAuditLog::record('refund', 'payment_order', (string)$this->record->id,
                            ['status' => PaymentOrderStatus::SUCCESS, 'amount' => $this->record->amount],
                            ['status' => $this->record->status, 'amount' => $this->record->amount],
                            '退款失败: ' . ($data['remark'] ?? '') . ' | ' . $result['msg']
                        );
                        Notification::make()->danger()->title('退款失败')->body($result['msg'])->send();
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
                                PaymentOrderStatus::SUCCESS => 'success', PaymentOrderStatus::PENDING => 'warning', PaymentOrderStatus::FAILED => 'danger', default => 'gray',
                            })
                            ->formatStateUsing(fn(string $state): string => PaymentOrderStatus::labels()[$state] ?? $state),
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
                                NotifyStatus::SUCCESS => 'success', NotifyStatus::PENDING => 'warning', NotifyStatus::FAILED => 'danger', default => 'gray',
                            })
                            ->formatStateUsing(fn(?string $state): string => NotifyStatus::labels()[$state] ?? '无需发货'),
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
                                    ->color(fn(?string $state): string => match ($state) {
                                        PaymentFlowStatus::SUCCESS => 'success', PaymentFlowStatus::REFUND => 'danger', default => 'gray',
                                    })
                                    ->formatStateUsing(fn(?string $state): string => match ($state) {
                                        PaymentFlowStatus::SUCCESS => '支付成功', PaymentFlowStatus::REFUND => '已退款', PaymentFlowStatus::PENDING => '处理中', default => $state ?? '',
                                    }),
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
                                \Filament\Infolists\Components\TextEntry::make('url')
                                    ->label('通知地址')
                                    ->copyable()
                                    ->limit(50)
                                    ->columnSpanFull(),
                                \Filament\Infolists\Components\TextEntry::make('http_code')->label('HTTP状态码'),
                                \Filament\Infolists\Components\TextEntry::make('error_message')
                                    ->label('错误信息')
                                    ->visible(fn(?string $state): bool => filled($state)),
                                \Filament\Infolists\Components\TextEntry::make('response_body')
                                    ->label('响应内容')
                                    ->limit(200)
                                    ->copyable(),
                                \Filament\Infolists\Components\TextEntry::make('request_params')
                                    ->label('请求参数')
                                    ->formatStateUsing(fn(mixed $state): string =>
                                        is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : ($state ?? '-')
                                    )
                                    ->monospace()
                                    ->copyable()
                                    ->columnSpanFull(),
                            ])->columns(3)
                            ->defaultSort('created_at', 'desc'),
                    ])->visible(fn(\App\Models\PaymentOrder $record): bool => $record->gameNotifyLogs()->count() > 0),

                // === 操作日志 ===
                \Filament\Infolists\Components\Section::make('操作日志')
                    ->schema([
                        \Filament\Infolists\Components\RepeatableEntry::make('operationLogs')
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('created_at')->label('时间')->dateTime(),
                                \Filament\Infolists\Components\TextEntry::make('action')
                                    ->label('操作')
                                    ->badge()
                                    ->color(fn(string $state): string => match ($state) {
                                        'top_up' => 'success', 'refund' => 'danger', 'retry_notify' => 'warning', default => 'gray',
                                    })
                                    ->formatStateUsing(fn(string $state): string => match ($state) {
                                        'top_up' => '手动补单', 'refund' => '退款', 'retry_notify' => '重试发货', default => $state,
                                    }),
                                \Filament\Infolists\Components\TextEntry::make('operator')->label('操作人'),
                                \Filament\Infolists\Components\TextEntry::make('remark')->label('备注'),
                            ])->columns(4)
                            ->defaultSort('created_at', 'desc'),
                    ])->visible(fn(\App\Models\PaymentOrder $record): bool => $record->operationLogs()->count() > 0),
            ]);
    }
}
