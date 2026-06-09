<?php
namespace App\Filament\Resources;

use App\Enums\RecommendPosition;
use App\Filament\Resources\RecommendationResource\Pages;
use App\Models\Recommendation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RecommendationResource extends Resource
{
    protected static ?string $model = Recommendation::class;
    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationGroup = '游戏管理';
    protected static ?string $navigationLabel = '首页推荐';
    protected static ?string $modelLabel = '推荐位';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('position_code')
                ->options(RecommendPosition::labels())
                ->required()
                ->label('推荐位'),
            Forms\Components\TextInput::make('title')->required()->label('标题'),
            Forms\Components\TextInput::make('subtitle')->label('副标题'),
            Forms\Components\TextInput::make('image')->label('图片链接')->placeholder('https://example.com/image.png'),
            Forms\Components\Select::make('target_type')
                ->options(['game' => '游戏', 'url' => '外链', 'none' => '无'])
                ->default('game')
                ->reactive()
                ->label('跳转类型'),
            Forms\Components\Select::make('target_id')
                ->options(fn() => \App\Models\Game::pluck('name', 'id'))
                ->visible(fn(\Filament\Forms\Get $get): bool => $get('target_type') === 'game')
                ->searchable()
                ->label('关联游戏'),
            Forms\Components\TextInput::make('url')
                ->url()
                ->visible(fn(callable $get) => $get('target_type') === 'url')
                ->label('外链地址'),
            Forms\Components\TextInput::make('sort')->numeric()->default(0)->label('排序'),
            Forms\Components\Toggle::make('status')->default(true)->label('启用'),
            Forms\Components\DateTimePicker::make('start_at')->label('开始时间'),
            Forms\Components\DateTimePicker::make('end_at')->label('结束时间'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('position_code')
                ->formatStateUsing(fn(string $state): string => RecommendPosition::labels()[$state] ?? $state)
                ->badge()
                ->label('推荐位'),
            Tables\Columns\TextColumn::make('title')->label('标题')->searchable(),
            Tables\Columns\TextColumn::make('sort')->sortable()->label('排序'),
            Tables\Columns\IconColumn::make('status')->boolean()->label('启用'),
            Tables\Columns\TextColumn::make('start_at')->dateTime()->label('开始'),
            Tables\Columns\TextColumn::make('end_at')->dateTime()->label('结束'),
        ])->modifyQueryUsing(fn(Builder $query) => $query->orderBy('position_code')->orderBy('sort'))
        ->defaultSort('sort')
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecommendations::route('/'),
            'create' => Pages\CreateRecommendation::route('/create'),
            'edit' => Pages\EditRecommendation::route('/{record}/edit'),
        ];
    }
}
