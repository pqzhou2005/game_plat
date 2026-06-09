<?php
namespace App\Filament\Resources;

use App\Enums\NoticeType;
use App\Filament\Resources\NoticeResource\Pages;
use App\Models\Notice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class NoticeResource extends Resource
{
    protected static ?string $model = Notice::class;
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationGroup = '运营管理';
    protected static ?string $navigationLabel = '公告管理';
    protected static ?string $modelLabel = '公告';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->required()->label('标题'),
            Forms\Components\Select::make('type')
                ->options(NoticeType::labels())
                ->required()->label('类型'),
            Forms\Components\Select::make('game_id')
                ->relationship('game', 'name')
                ->searchable()
                ->nullable()
                ->label('关联游戏（选填，留空为平台公告）'),
            Forms\Components\Textarea::make('summary')->label('摘要')->rows(2),
            Forms\Components\RichEditor::make('content')->label('正文'),
            Forms\Components\Toggle::make('is_top')->label('置顶')->default(false),
            Forms\Components\Toggle::make('status')->label('发布')->default(true),
            Forms\Components\DateTimePicker::make('published_at')->label('发布时间')->default(now()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')->label('标题')->searchable(),
            Tables\Columns\TextColumn::make('type')
                ->formatStateUsing(fn(string $state): string => match ($state) {
                    'platform' => '平台', 'game' => '游戏', 'maintenance' => '维护', 'activity' => '活动', 'merge' => '合服', default => $state,
                })
                ->badge()->label('类型'),
            Tables\Columns\TextColumn::make('game.name')->label('关联游戏'),
            Tables\Columns\IconColumn::make('is_top')->boolean()->label('置顶'),
            Tables\Columns\IconColumn::make('status')->boolean()->label('发布'),
            Tables\Columns\TextColumn::make('published_at')->dateTime()->label('发布时间')->sortable(),
        ])->defaultSort('is_top', 'desc')->defaultSort('published_at', 'desc')
        ->actions([
            Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotices::route('/'),
            'create' => Pages\CreateNotice::route('/create'),
            'edit' => Pages\EditNotice::route('/{record}/edit'),
        ];
    }
}
