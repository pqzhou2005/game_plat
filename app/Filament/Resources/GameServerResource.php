<?php
namespace App\Filament\Resources;

use App\Filament\Resources\GameServerResource\Pages;
use App\Models\GameServer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GameServerResource extends Resource
{
    protected static ?string $model = GameServer::class;
    protected static ?string $navigationIcon = 'heroicon-o-server-stack';
    protected static ?string $navigationGroup = '游戏管理';
    protected static ?string $navigationLabel = '区服管理';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('game_id')
                ->relationship('game', 'name')->required()->label('所属游戏'),
            Forms\Components\TextInput::make('name')->required()->label('区服名'),
            Forms\Components\TextInput::make('server_id')->label('服务端ID'),
            Forms\Components\DateTimePicker::make('open_time')->required()->label('开服时间'),
            Forms\Components\Select::make('status')
                ->options([1 => '火爆', 2 => '推荐', 3 => '维护', 4 => '已满'])->default(1)->label('状态'),
            Forms\Components\Toggle::make('is_recommend')->label('推荐'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('game.name')->label('游戏'),
            Tables\Columns\TextColumn::make('name')->label('区服名'),
            Tables\Columns\TextColumn::make('open_time')->dateTime()->sortable()->label('开服时间'),
            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->color(fn(int $state): string => match ($state) { 1 => 'success', 2 => 'warning', 3 => 'danger', 4 => 'gray' })
                ->formatStateUsing(fn(int $state): string => match ($state) { 1 => '火爆', 2 => '推荐', 3 => '维护', 4 => '已满' })
                ->label('状态'),
        ])->defaultSort('open_time', 'desc')
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGameServers::route('/'),
            'create' => Pages\CreateGameServer::route('/create'),
            'edit' => Pages\EditGameServer::route('/{record}/edit'),
        ];
    }
}
