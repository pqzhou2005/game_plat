<?php
namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationGroup = '用户管理';
    protected static ?string $navigationLabel = '玩家用户';
    protected static ?string $pluralModelLabel = '用户';
    protected static ?string $modelLabel = '用户';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('username')->required()->label('用户名'),
            Forms\Components\TextInput::make('mobile')->label('手机号'),
            Forms\Components\TextInput::make('email')->email()->label('邮箱'),
            Forms\Components\TextInput::make('password')
                ->password()
                ->dehydrated(fn($state) => filled($state))
                ->required(fn(string $context) => $context === 'create')
                ->label('密码'),
            Forms\Components\Toggle::make('status')->label('启用')->default(true),
            Forms\Components\TextInput::make('real_name')->label('真实姓名'),
            Forms\Components\TextInput::make('id_card')->label('身份证号')
                ->afterStateHydrated(fn($component, $record) => $component->state($record?->masked_id_card)),
            Forms\Components\DateTimePicker::make('id_card_verified_at')->label('实名认证时间'),
            Forms\Components\Placeholder::make('real_name_status')
                ->label('实名状态')
                ->content(fn(?User $record): string => $record?->real_name_status ?? '未实名'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('username')->searchable()->label('用户名'),
            Tables\Columns\TextColumn::make('mobile')->label('手机号'),
            Tables\Columns\TextColumn::make('email')->label('邮箱'),
            Tables\Columns\IconColumn::make('status')->boolean()->label('状态'),
            Tables\Columns\TextColumn::make('real_name_status')
                ->label('实名状态')
                ->badge()
                ->color(fn(?User $record): string => match ($record?->real_name_status) {
                    '未实名' => 'danger',
                    '未成年' => 'warning',
                    '已成年' => 'success',
                    default => 'gray',
                })
                ->getStateUsing(fn(User $record): string => $record->real_name_status),
            Tables\Columns\TextColumn::make('masked_id_card')
                ->label('身份证号')
                ->getStateUsing(fn(User $record): ?string => $record->masked_id_card),
            Tables\Columns\TextColumn::make('last_login_at')->dateTime()->label('最后登录'),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->label('注册时间'),
        ])->filters([
            Tables\Filters\TernaryFilter::make('status')->label('状态'),
            Tables\Filters\SelectFilter::make('id_card_verified_at')
                ->label('实名状态')
                ->options([
                    'verified' => '已实名',
                    'unverified' => '未实名',
                ])
                ->query(fn($query, array $data) => match ($data['value'] ?? null) {
                    'verified' => $query->whereNotNull('id_card_verified_at'),
                    'unverified' => $query->whereNull('id_card_verified_at'),
                    default => $query,
                }),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
