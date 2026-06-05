<?php
namespace App\Filament\Resources;

use App\Filament\Resources\GameCategoryResource\Pages;
use App\Models\GameCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GameCategoryResource extends Resource
{
    protected static ?string $model = GameCategory::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = '游戏管理';
    protected static ?string $navigationLabel = '游戏分类';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->label('分类名'),
            Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true)->label('别名'),
            Forms\Components\TextInput::make('sort')->numeric()->default(0)->label('排序'),
            Forms\Components\Toggle::make('status')->default(true)->label('启用'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('分类名'),
            Tables\Columns\TextColumn::make('slug')->label('别名'),
            Tables\Columns\TextColumn::make('sort')->sortable()->label('排序'),
            Tables\Columns\IconColumn::make('status')->boolean()->label('状态'),
            Tables\Columns\TextColumn::make('games_count')->counts('games')->label('游戏数'),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGameCategories::route('/'),
            'create' => Pages\CreateGameCategory::route('/create'),
            'edit' => Pages\EditGameCategory::route('/{record}/edit'),
        ];
    }
}
