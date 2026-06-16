<?php
namespace App\Filament\Pages;

use App\Enums\NotifyStatus;
use App\Enums\PaymentOrderStatus;
use App\Filament\Resources\PaymentOrderResource;
use App\Models\PaymentOrder;
use App\Models\PaymentOperationLog;
use App\Services\GamePayService;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;

class OrderReconciliation extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';
    protected static ?string $navigationGroup = '支付管理';
    protected static ?string $navigationLabel = '订单对账';
    protected static ?string $title = '订单对账';
    protected static string $view = 'filament.pages.order-reconciliation';

    public function table(Table $table): Table
    {
        return $table
            ->query(PaymentOrder::with(['user', 'game', 'latestFlow']))
            ->columns([
                Tables\Columns\TextColumn::make('order_no')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('订单号已复制')
                    ->label('订单号')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_id')
                    ->label('用户ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('user.username')
                    ->searchable()
                    ->label('用户名'),
                Tables\Columns\TextColumn::make('game.name')
                    ->label('游戏')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('server_id')
                    ->label('区服ID')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('role_id')
                    ->label('角色ID')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('role_name')
                    ->label('角色名')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('amount')
                    ->money('CNY')
                    ->sortable()
                    ->label('金额'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        PaymentOrderStatus::SUCCESS => 'success',
                        PaymentOrderStatus::PENDING => 'gray',
                        PaymentOrderStatus::FAILED => 'danger',
                        PaymentOrderStatus::CLOSED => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => PaymentOrderStatus::labels()[$state] ?? $state)
                    ->sortable()
                    ->label('订单状态'),
                Tables\Columns\TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable()
                    ->label('支付时间')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('notify_status')
                    ->badge()
                    ->color(fn(?string $state): string => match ($state) {
                        NotifyStatus::SUCCESS => 'success',
                        NotifyStatus::PENDING => 'warning',
                        NotifyStatus::FAILED => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(?string $state): string => NotifyStatus::labels()[$state] ?? '无需发货')
                    ->label('发货状态'),
                Tables\Columns\TextColumn::make('notify_times')
                    ->sortable()
                    ->label('发货次数')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('last_notify_at')
                    ->dateTime()
                    ->sortable()
                    ->label('最后发货时间')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('latestFlow.channel')
                    ->formatStateUsing(fn(?string $state): string => match ($state) {
                        'alipay' => '支付宝', 'wechat' => '微信支付', null => '-', default => $state,
                    })
                    ->label('支付渠道'),
                Tables\Columns\TextColumn::make('latestFlow.channel_order_no')
                    ->label('渠道流水号')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('创建时间'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(PaymentOrderStatus::labels())
                    ->label('订单状态')
                    ->multiple(),
                Tables\Filters\SelectFilter::make('notify_status')
                    ->options(NotifyStatus::labels())
                    ->label('发货状态')
                    ->multiple(),
                Tables\Filters\SelectFilter::make('game_id')
                    ->relationship('game', 'name')
                    ->searchable()
                    ->label('游戏'),
                Tables\Filters\Filter::make('paid_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('paid_from')->label('支付起始'),
                        \Filament\Forms\Components\DatePicker::make('paid_until')->label('支付截止'),
                    ])
                    ->query(fn(Builder $query, array $data): Builder => $query
                        ->when($data['paid_from'], fn(Builder $q) => $q->whereDate('paid_at', '>=', $data['paid_from']))
                        ->when($data['paid_until'], fn(Builder $q) => $q->whereDate('paid_at', '<=', $data['paid_until'])))
                    ->label('支付时间'),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('created_from')->label('创建起始'),
                        \Filament\Forms\Components\DatePicker::make('created_until')->label('创建截止'),
                    ])
                    ->query(fn(Builder $query, array $data): Builder => $query
                        ->when($data['created_from'], fn(Builder $q) => $q->whereDate('created_at', '>=', $data['created_from']))
                        ->when($data['created_until'], fn(Builder $q) => $q->whereDate('created_at', '<=', $data['created_until'])))
                    ->label('创建时间'),
                Tables\Filters\Filter::make('amount')
                    ->form([
                        TextInput::make('amount_min')->numeric()->label('金额下限'),
                        TextInput::make('amount_max')->numeric()->label('金额上限'),
                    ])
                    ->query(fn(Builder $query, array $data): Builder => $query
                        ->when($data['amount_min'], fn(Builder $q) => $q->where('amount', '>=', $data['amount_min']))
                        ->when($data['amount_max'], fn(Builder $q) => $q->where('amount', '<=', $data['amount_max'])))
                    ->label('金额范围'),
            ])
            ->actions([
                // 手动补发
                Tables\Actions\Action::make('manual_notify')
                    ->label('手动补发')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn(PaymentOrder $record): bool =>
                        $record->status === PaymentOrderStatus::SUCCESS
                        && $record->notify_status !== NotifyStatus::SUCCESS
                        && $record->game_id !== null
                    )
                    ->requiresConfirmation()
                    ->action(function (PaymentOrder $record): void {
                        try {
                            $result = app(GamePayService::class)->notifyGameServer($record);
                            PaymentOperationLog::log($record->id, 'manual_notify',
                                '对账页手动补发',
                                ['result' => $result]
                            );
                            if ($result) {
                                Notification::make()->success()->title('补发成功')->send();
                            } else {
                                Notification::make()->warning()->title('补发失败')->body('请检查游戏方配置后重试')->send();
                            }
                        } catch (\Exception $e) {
                            PaymentOperationLog::log($record->id, 'manual_notify',
                                '补发异常',
                                ['error' => $e->getMessage()]
                            );
                            Notification::make()->danger()->title('补发异常')->body($e->getMessage())->send();
                        }
                    }),

                // 标记发货成功
                Tables\Actions\Action::make('mark_notify_success')
                    ->label('标记发货成功')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(PaymentOrder $record): bool =>
                        $record->status === PaymentOrderStatus::SUCCESS
                        && $record->notify_status !== NotifyStatus::SUCCESS
                    )
                    ->form([
                        Textarea::make('remark')
                            ->label('备注（必填）')
                            ->required()
                            ->placeholder('说明标记原因，如：游戏方已确认到账'),
                    ])
                    ->requiresConfirmation()
                    ->action(function (PaymentOrder $record, array $data): void {
                        $record->update(['notify_status' => NotifyStatus::SUCCESS]);
                        PaymentOperationLog::log($record->id, 'mark_notify_success',
                            $data['remark'] ?? null,
                            ['force' => true]
                        );
                        Notification::make()->success()->title('已标记发货成功')->send();
                    }),

                // 关闭订单
                Tables\Actions\Action::make('close_order')
                    ->label('关闭订单')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(PaymentOrder $record): bool =>
                        $record->status === PaymentOrderStatus::PENDING
                    )
                    ->form([
                        Textarea::make('remark')
                            ->label('备注（必填）')
                            ->required()
                            ->placeholder('关闭原因，如：用户未支付超时'),
                    ])
                    ->requiresConfirmation()
                    ->action(function (PaymentOrder $record, array $data): void {
                        $record->update(['status' => PaymentOrderStatus::CLOSED]);
                        PaymentOperationLog::log($record->id, 'close_order',
                            $data['remark'] ?? null,
                            ['prev_status' => PaymentOrderStatus::PENDING]
                        );
                        Notification::make()->success()->title('订单已关闭')->send();
                    }),

                // 查看详情
                Tables\Actions\Action::make('view_detail')
                    ->label('详情')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn(PaymentOrder $record): string =>
                        PaymentOrderResource::getUrl('view', ['record' => $record])
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('batch_retry')
                    ->label('批量重试发货')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function (\Illuminate\Support\Collection $records): void {
                        $success = 0;
                        $fail = 0;
                        foreach ($records as $record) {
                            if ($record->status === PaymentOrderStatus::SUCCESS
                                && $record->notify_status !== NotifyStatus::SUCCESS
                                && $record->game_id !== null
                            ) {
                                try {
                                    $result = app(GamePayService::class)->notifyGameServer($record);
                                    $result ? $success++ : $fail++;
                                    PaymentOperationLog::log($record->id, 'manual_notify',
                                        '对账页批量重试', ['result' => $result]
                                    );
                                } catch (\Exception $e) {
                                    $fail++;
                                    PaymentOperationLog::log($record->id, 'manual_notify',
                                        '批量重试异常',
                                        ['error' => $e->getMessage()]
                                    );
                                }
                            }
                        }
                        Notification::make()
                            ->title('批量重试完成')
                            ->body("成功: {$success}, 失败: {$fail}")
                            ->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->poll('30s')

            // === 快捷 Tab 筛选 ===
            ->tabs([
                'all' => Tables\Tabs\Tab::make('全部订单')
                    ->icon('heroicon-o-list-bullet'),
                'pending_payment' => Tables\Tabs\Tab::make('待支付')
                    ->icon('heroicon-o-clock')
                    ->badge(fn() => PaymentOrder::where('status', PaymentOrderStatus::PENDING)->count())
                    ->modifyQueryUsing(fn(Builder $query) => $query->where('status', PaymentOrderStatus::PENDING)),
                'paid_success' => Tables\Tabs\Tab::make('支付成功')
                    ->icon('heroicon-o-check-circle')
                    ->badge(fn() => PaymentOrder::where('status', PaymentOrderStatus::SUCCESS)->count())
                    ->modifyQueryUsing(fn(Builder $query) => $query->where('status', PaymentOrderStatus::SUCCESS)),
                'deliver_success' => Tables\Tabs\Tab::make('发货成功')
                    ->icon('heroicon-o-truck')
                    ->modifyQueryUsing(fn(Builder $query) => $query
                        ->where('status', PaymentOrderStatus::SUCCESS)
                        ->where('notify_status', NotifyStatus::SUCCESS)),
                'deliver_failed' => Tables\Tabs\Tab::make('发货失败')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->badge(fn() => PaymentOrder::where('status', PaymentOrderStatus::SUCCESS)
                        ->where('notify_status', NotifyStatus::FAILED)->count())
                    ->modifyQueryUsing(fn(Builder $query) => $query
                        ->where('status', PaymentOrderStatus::SUCCESS)
                        ->where('notify_status', NotifyStatus::FAILED)),
                'pending_deliver' => Tables\Tabs\Tab::make('待发货/通知中')
                    ->icon('heroicon-o-arrow-path')
                    ->badge(fn() => PaymentOrder::where('status', PaymentOrderStatus::SUCCESS)
                        ->where(function (Builder $q) {
                            $q->whereNull('notify_status')
                              ->orWhere('notify_status', NotifyStatus::PENDING);
                        })->count())
                    ->modifyQueryUsing(fn(Builder $query) => $query
                        ->where('status', PaymentOrderStatus::SUCCESS)
                        ->where(function (Builder $q) {
                            $q->whereNull('notify_status')
                              ->orWhere('notify_status', NotifyStatus::PENDING);
                        })),
                'abnormal' => Tables\Tabs\Tab::make('异常订单')
                    ->icon('heroicon-o-shield-exclamation')
                    ->badge(fn() => PaymentOrder::where(function (Builder $q) {
                        $q->where(function (Builder $q2) {
                            $q2->where('status', PaymentOrderStatus::SUCCESS)
                               ->where(function (Builder $q3) {
                                   $q3->whereNull('notify_status')
                                      ->orWhere('notify_status', '<>', NotifyStatus::SUCCESS);
                               });
                        })->orWhere(function (Builder $q2) {
                            $q2->where('status', PaymentOrderStatus::SUCCESS)
                               ->where('notify_status', NotifyStatus::FAILED);
                        })->orWhere(function (Builder $q2) {
                            $q2->where('status', PaymentOrderStatus::PENDING)
                               ->where('created_at', '<', now()->subMinutes(10));
                        });
                    })->count())
                    ->modifyQueryUsing(function (Builder $query) {
                        return $query->where(function (Builder $q) {
                            $q->where(function (Builder $q2) {
                                $q2->where('status', PaymentOrderStatus::SUCCESS)
                                   ->where(function (Builder $q3) {
                                       $q3->whereNull('notify_status')
                                          ->orWhere('notify_status', '<>', NotifyStatus::SUCCESS);
                                   });
                            })->orWhere(function (Builder $q2) {
                                $q2->where('status', PaymentOrderStatus::SUCCESS)
                                   ->where('notify_status', NotifyStatus::FAILED);
                            })->orWhere(function (Builder $q2) {
                                $q2->where('status', PaymentOrderStatus::PENDING)
                                   ->where('created_at', '<', now()->subMinutes(10));
                            });
                        });
                    }),
            ]);
    }
}
