<?php
namespace App\Filament\Resources;

use App\Enums\AdminRole;
use App\Filament\Resources\AdminUserResource\Pages;
use App\Models\AdminUser;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AdminUserResource extends Resource
{
    protected static ?string $model = AdminUser::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = '系统管理';
    protected static ?string $navigationLabel = '后台账号';
    protected static ?string $modelLabel = '后台账号';
    protected static ?string $pluralModelLabel = '后台账号';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('username')->required()->label('用户名')
                ->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('name')->label('姓名'),
            Forms\Components\TextInput::make('email')->email()->label('邮箱')
                ->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('password')
                ->password()
                ->dehydrated(fn($state) => filled($state))
                ->required(fn(string $context) => $context === 'create')
                ->label('密码'),
            Forms\Components\Select::make('role')
                ->options(AdminRole::labels())
                ->required()
                ->label('角色'),
            Forms\Components\Toggle::make('status')->default(true)->label('启用'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('username')->searchable()->label('用户名'),
            Tables\Columns\TextColumn::make('name')->label('姓名'),
            Tables\Columns\TextColumn::make('email')->label('邮箱'),
            Tables\Columns\TextColumn::make('role')
                ->badge()
                ->color(fn(string $state): string => match ($state) {
                    AdminRole::SUPER_ADMIN => 'danger', AdminRole::ADMIN => 'warning',
                    AdminRole::FINANCE => 'success', AdminRole::SUPPORT => 'info',
                    AdminRole::OPERATOR => 'gray', default => 'gray',
                })
                ->formatStateUsing(fn(string $state): string => AdminRole::labels()[$state] ?? $state)
                ->label('角色'),
            Tables\Columns\IconColumn::make('status')->boolean()->label('状态'),
            Tables\Columns\TextColumn::make('last_login_at')->dateTime()->label('最后登录'),
            Tables\Columns\TextColumn::make('last_login_ip')->label('最后IP'),
        ])->defaultSort('created_at', 'desc')
        ->actions([
            Tables\Actions\EditAction::make(),
        ]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminUsers::route('/'),
            'create' => Pages\CreateAdminUser::route('/create'),
            'edit' => Pages\EditAdminUser::route('/{record}/edit'),
        ];
    }
}
