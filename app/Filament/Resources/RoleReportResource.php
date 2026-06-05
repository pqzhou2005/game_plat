<?php
namespace App\Filament\Resources;

use App\Filament\Resources\RoleReportResource\Pages;
use App\Models\RoleReport;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RoleReportResource extends Resource
{
    protected static ?string $model = RoleReport::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = '游戏管理';
    protected static ?string $navigationLabel = '角色上报';
    protected static ?string $modelLabel = '角色上报';

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('user.username')->label('用户'),
            Tables\Columns\TextColumn::make('game.name')->label('游戏'),
            Tables\Columns\TextColumn::make('submit_type')
                ->formatStateUsing(fn(int $state): string => match ($state) { 1 => '创角', 2 => '升级', 3 => '进入游戏', 4 => '改名', default => '未知' })
                ->badge()
                ->color(fn(int $state): string => match ($state) { 1 => 'success', 2 => 'warning', 3 => 'info', 4 => 'gray', default => 'gray' })
                ->label('类型'),
            Tables\Columns\TextColumn::make('server_id')->label('区服ID'),
            Tables\Columns\TextColumn::make('role_id')->label('角色ID'),
            Tables\Columns\TextColumn::make('role_name')->label('角色名'),
            Tables\Columns\TextColumn::make('role_level')->label('等级'),
            Tables\Columns\TextColumn::make('create_time')->dateTime()->label('游戏服时间'),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->label('上报时间'),
        ])->defaultSort('created_at', 'desc')
        ->filters([
            Tables\Filters\SelectFilter::make('submit_type')
                ->options([1 => '创角', 2 => '升级', 3 => '进入游戏', 4 => '改名'])->label('类型'),
        ]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array
    {
        return ['index' => Pages\ListRoleReports::route('/')];
    }
}
