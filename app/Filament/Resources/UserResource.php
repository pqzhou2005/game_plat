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
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = '用户管理';
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
            Forms\Components\TextInput::make('id_card')->label('身份证号'),
            Forms\Components\DateTimePicker::make('id_card_verified_at')->label('实名认证时间'),
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
            Tables\Columns\IconColumn::make('id_card_verified_at')
                ->boolean(fn($state) => filled($state))
                ->label('实名认证'),
            Tables\Columns\TextColumn::make('last_login_at')->dateTime()->label('最后登录'),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->label('注册时间'),
        ])->filters([
            Tables\Filters\TernaryFilter::make('status')->label('状态'),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
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
