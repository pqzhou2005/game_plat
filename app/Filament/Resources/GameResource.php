<?php
namespace App\Filament\Resources;

use App\Filament\Resources\GameResource\Pages;
use App\Models\Game;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GameResource extends Resource
{
    protected static ?string $model = Game::class;
    protected static ?string $navigationIcon = 'heroicon-o-play';
    protected static ?string $navigationGroup = '游戏管理';
    protected static ?string $navigationLabel = '游戏管理';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('category_id')
                ->relationship('category', 'name')->required()->label('分类'),
            Forms\Components\TextInput::make('name')->required()->label('游戏名'),
            Forms\Components\TextInput::make('short_name')->label('简称'),
            Forms\Components\TextInput::make('logo')->label('LOGO链接')->placeholder('https://example.com/logo.png'),
            Forms\Components\TextInput::make('banner')->label('Banner链接')->placeholder('https://example.com/banner.png'),
            Forms\Components\Select::make('game_type')
                ->options(['页游' => '页游', '微端' => '微端', '手游' => '手游'])->label('游戏类型'),
            Forms\Components\TagsInput::make('tags')->label('标签'),
            Forms\Components\RichEditor::make('description')->label('游戏介绍'),
            Forms\Components\TextInput::make('developer')->label('开发商'),
            Forms\Components\TextInput::make('operator')->label('运营商'),
            Forms\Components\TextInput::make('sort')->numeric()->default(0)->label('排序'),
            Forms\Components\Toggle::make('status')->default(true)->label('上架'),
            Forms\Components\Toggle::make('is_recommend')->label('推荐'),
            Forms\Components\Toggle::make('is_hot')->label('热门'),
            Forms\Components\Toggle::make('is_new')->label('新游'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable()->label('游戏名'),
            Tables\Columns\TextColumn::make('category.name')->label('分类'),
            Tables\Columns\TextColumn::make('game_type')->label('类型'),
            Tables\Columns\IconColumn::make('is_recommend')->boolean()->label('推荐'),
            Tables\Columns\IconColumn::make('is_hot')->boolean()->label('热门'),
            Tables\Columns\IconColumn::make('is_new')->boolean()->label('新游'),
            Tables\Columns\IconColumn::make('status')->boolean()->label('上架'),
        ])->filters([
            Tables\Filters\SelectFilter::make('category_id')
                ->relationship('category', 'name')->label('分类'),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGames::route('/'),
            'create' => Pages\CreateGame::route('/create'),
            'edit' => Pages\EditGame::route('/{record}/edit'),
        ];
    }
}
