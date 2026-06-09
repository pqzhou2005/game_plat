<?php
namespace App\Filament\Resources;

use App\Filament\Resources\PaymentConfigResource\Pages;
use App\Models\PaymentConfig;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class PaymentConfigResource extends Resource
{
    protected static ?string $model = PaymentConfig::class;
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationGroup = '支付管理';
    protected static ?string $navigationLabel = '支付渠道配置';
    protected static ?string $modelLabel = '支付渠道';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('channel')
                ->options(['alipay' => '支付宝', 'wechat' => '微信支付'])
                ->disabled(fn(?PaymentConfig $record): bool => $record !== null)
                ->required()
                ->label('渠道'),
            Forms\Components\KeyValue::make('config')
                ->keyLabel('参数名')
                ->valueLabel('参数值')
                ->required()
                ->label('配置参数')
                ->hint('支付宝: app_id, app_secret_cert, sign_type | 微信: mch_id, mch_secret_key, app_id'),
            Forms\Components\Toggle::make('enabled')
                ->default(true)
                ->label('启用'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('channel')
                ->formatStateUsing(fn(string $state): string => match ($state) {
                    'alipay' => '支付宝', 'wechat' => '微信支付', default => $state,
                })
                ->badge()
                ->color(fn(string $state): string => $state === 'alipay' ? 'primary' : 'success')
                ->label('渠道'),
            Tables\Columns\TextColumn::make('config')
                ->formatStateUsing(fn(?array $state): string => $state ? implode(', ', array_keys($state)) : '')
                ->label('已配置参数'),
            Tables\Columns\IconColumn::make('enabled')
                ->boolean()
                ->label('状态'),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ]);
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentConfigs::route('/'),
            'create' => Pages\CreatePaymentConfig::route('/create'),
            'edit' => Pages\EditPaymentConfig::route('/{record}/edit'),
        ];
    }
}
