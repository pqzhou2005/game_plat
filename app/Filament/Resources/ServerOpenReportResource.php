<?php
namespace App\Filament\Resources;

use App\Filament\Resources\ServerOpenReportResource\Pages;
use App\Models\ServerOpenReport;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServerOpenReportResource extends Resource
{
    protected static ?string $model = ServerOpenReport::class;
    protected static ?string $navigationIcon = 'heroicon-o-server';
    protected static ?string $navigationGroup = '游戏管理';
    protected static ?string $navigationLabel = '开服上报';
    protected static ?string $modelLabel = '开服上报';

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('game.name')->label('游戏'),
            Tables\Columns\TextColumn::make('project')->label('项目'),
            Tables\Columns\TextColumn::make('open_server')->label('已开服'),
            Tables\Columns\TextColumn::make('open_server_time')->dateTime()->label('开服时间'),
            Tables\Columns\TextColumn::make('created_role_num')->label('创角人数'),
            Tables\Columns\TextColumn::make('pay_num')->label('充值人数'),
            Tables\Columns\TextColumn::make('preset_open_server')->label('预开服'),
            Tables\Columns\TextColumn::make('created_at')->dateTime()->label('上报时间'),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array
    {
        return ['index' => Pages\ListServerOpenReports::route('/')];
    }
}
