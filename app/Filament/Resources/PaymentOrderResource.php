<?php
namespace App\Filament\Resources;

use App\Enums\NotifyStatus;
use App\Enums\PaymentOrderStatus;
use App\Filament\Resources\PaymentOrderResource\Pages;
use App\Models\AdminAuditLog;
use App\Models\PaymentOperationLog;
use App\Models\PaymentOrder;
use App\Services\GamePayService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Illuminate\Support\Collection;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentOrderResource extends Resource
{
    protected static ?string $model = PaymentOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-yen';
    protected static ?string $navigationGroup = '支付管理';
    protected static ?string $navigationLabel = '订单管理';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('order_no')->disabled()->label('订单号'),
            Forms\Components\TextInput::make('amount')->disabled()->label('金额'),
            Forms\Components\TextInput::make('notify_status')->disabled()->label('发货状态'),
            Forms\Components\TextInput::make('notify_times')->disabled()->label('重试次数'),
            Forms\Components\Select::make('status')
                ->options(PaymentOrderStatus::labels())
                ->label('状态'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('order_no')->searchable()->label('订单号'),
            Tables\Columns\TextColumn::make('user.username')->label('用户'),
            Tables\Columns\TextColumn::make('amount')->money('CNY')->label('金额'),
            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->color(fn(string $state): string => match ($state) {
                    PaymentOrderStatus::SUCCESS => 'success', PaymentOrderStatus::PENDING => 'warning',
                    PaymentOrderStatus::FAILED => 'danger', PaymentOrderStatus::CLOSED => 'gray', default => 'gray',
                })
                ->formatStateUsing(fn(string $state): string => PaymentOrderStatus::labels()[$state] ?? $state)
                ->label('状态'),
            Tables\Columns\TextColumn::make('notify_status')
                ->badge()
                ->color(fn(?string $state): string => match ($state) {
                    NotifyStatus::SUCCESS => 'success', NotifyStatus::PENDING => 'warning',
                    NotifyStatus::FAILED => 'danger', default => 'gray',
                })
                ->formatStateUsing(fn(?string $state): string => NotifyStatus::labels()[$state] ?? '无需发货')
                ->label('发货'),
            Tables\Columns\TextColumn::make('notify_times')->label('重试'),
            Tables\Columns\TextColumn::make('paid_at')->dateTime()->label('支付时间'),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->label('创建时间'),
        ])->defaultSort('created_at', 'desc')
        ->filters([
            Tables\Filters\SelectFilter::make('status')
                ->options(PaymentOrderStatus::labels())->label('状态'),
            Tables\Filters\SelectFilter::make('notify_status')
                ->options(NotifyStatus::labels())->label('发货状态'),
        ])->bulkActions([
            Tables\Actions\BulkAction::make('batch_retry')
                ->label('批量重试发货')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->action(function (Collection $records): void {
                    $success = 0;
                    $fail = 0;
                    foreach ($records as $record) {
                        if ($record->status === PaymentOrderStatus::SUCCESS && $record->notify_status !== NotifyStatus::SUCCESS) {
                            try {
                                $result = app(GamePayService::class)->notifyGameServer($record);
                                if ($result) {
                                    $success++;
                                } else {
                                    $fail++;
                                }
                                PaymentOperationLog::log($record->id, 'retry_notify', '批量重试', ['result' => $result ?? false]);
                                AdminAuditLog::record('retry_notify', 'payment_order', (string)$record->id, null, null, '批量重试');
                            } catch (\Exception $e) {
                                $fail++;
                            }
                        }
                    }
                    Notification::make()->title('批量重试完成')->body("成功: {$success}, 失败: {$fail}")->send();
                }),
        ])->actions([
            Tables\Actions\Action::make('retry_notify')
                ->label('重试发货')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn(PaymentOrder $record): bool =>
                    $record->status === PaymentOrderStatus::SUCCESS && $record->notify_status !== NotifyStatus::SUCCESS
                )
                ->action(function (PaymentOrder $record): void {
                    try {
                        $result = app(GamePayService::class)->notifyGameServer($record);
                        PaymentOperationLog::log($record->id, 'retry_notify', '列表页重试', ['result' => $result]);
                        AdminAuditLog::record('retry_notify', 'payment_order', (string)$record->id,
                            ['notify_status' => $record->notify_status],
                            ['notify_status' => $result ? NotifyStatus::SUCCESS : ($record->notify_times >= 3 ? NotifyStatus::FAILED : NotifyStatus::PENDING)],
                            '列表页重试'
                        );
                        if ($result) {
                            Notification::make()->success()->title('发货成功')->send();
                        } else {
                            Notification::make()->warning()->title('发货失败')->body('请检查游戏方配置后重试')->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()->danger()->title('发货异常')->body($e->getMessage())->send();
                    }
                }),
            Tables\Actions\ViewAction::make()->label('详情'),
            Tables\Actions\EditAction::make(),
        ]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditPaymentOrder::route('/{record}/edit'),
        ];
    }
}
