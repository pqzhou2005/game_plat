<?php
namespace App\Filament\Resources;

use App\Filament\Resources\PaymentOrderResource\Pages;
use App\Models\PaymentOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
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
            Forms\Components\Select::make('status')
                ->options(['pending' => '处理中', 'success' => '成功', 'failed' => '失败', 'closed' => '已关闭'])
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
                    'success' => 'success', 'pending' => 'warning', 'failed' => 'danger', default => 'gray',
                })
                ->formatStateUsing(fn(string $state): string => match ($state) {
                    'success' => '成功', 'pending' => '处理中', 'failed' => '失败', default => $state,
                })
                ->label('状态'),
            Tables\Columns\TextColumn::make('paid_at')->dateTime()->label('支付时间'),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->label('创建时间'),
        ])->defaultSort('created_at', 'desc')
        ->filters([
            Tables\Filters\SelectFilter::make('status')
                ->options(['pending' => '处理中', 'success' => '成功', 'failed' => '失败'])->label('状态'),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentOrders::route('/'),
            'edit' => Pages\EditPaymentOrder::route('/{record}/edit'),
        ];
    }
}
