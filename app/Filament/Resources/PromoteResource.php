<?php
namespace App\Filament\Resources;

use App\Filament\Resources\PromoteResource\Pages;
use App\Models\Promote;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PromoteResource extends Resource
{
    protected static ?string $model = Promote::class;
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationGroup = '推广管理';
    protected static ?string $navigationLabel = '推广入口';
    protected static ?string $modelLabel = '推广入口';
    protected static ?string $pluralModelLabel = '推广入口';
    protected static ?string $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('基本信息')->schema([
                Forms\Components\TextInput::make('promote_name')
                    ->required()
                    ->maxLength(100)
                    ->label('推广名称'),
                Forms\Components\Select::make('game_id')
                    ->relationship('game', 'name')
                    ->searchable()
                    ->nullable()
                    ->label('关联游戏'),
                Forms\Components\Textarea::make('remark')
                    ->maxLength(255)
                    ->label('备注'),
            ]),

            Forms\Components\Section::make('落地页配置')
                ->description('配置推广落地页的展示内容，访问链接：/p/{promote_code}')
                ->schema([
                    Forms\Components\TextInput::make('landing_title')
                        ->maxLength(100)
                        ->label('落地页标题'),
                    Forms\Components\TextInput::make('landing_subtitle')
                        ->maxLength(255)
                        ->label('副标题'),
                    Forms\Components\TextInput::make('landing_hero_image')
                        ->maxLength(500)
                        ->label('主图链接')
                        ->placeholder('https://example.com/hero.png'),
                    Forms\Components\TextInput::make('landing_background')
                        ->maxLength(500)
                        ->label('背景图/背景色')
                        ->placeholder('#f5f5f5 或 https://example.com/bg.png'),
                    Forms\Components\TextInput::make('landing_button_text')
                        ->maxLength(50)
                        ->default('立即注册')
                        ->label('按钮文案'),
                    Forms\Components\ColorPicker::make('landing_theme_color')
                        ->default('#ff7a00')
                        ->label('主题色'),
                    Forms\Components\Repeater::make('landing_features')
                        ->label('卖点列表')
                        ->schema([
                            Forms\Components\TextInput::make('icon')
                                ->label('图标（Emoji 或 图标名）')
                                ->default('🎮'),
                            Forms\Components\TextInput::make('title')
                                ->label('标题')
                                ->required(),
                            Forms\Components\Textarea::make('description')
                                ->label('描述'),
                        ])
                        ->defaultItems(0)
                        ->addActionLabel('添加卖点')
                        ->reorderable()
                        ->collapsible(),
                    Forms\Components\KeyValue::make('landing_content')
                        ->label('扩展内容')
                        ->addActionLabel('添加扩展项'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')
                ->sortable()
                ->label('ID'),
            Tables\Columns\TextColumn::make('promote_code')
                ->searchable()
                ->copyable()
                ->copyMessage('推广码已复制')
                ->label('推广码'),
            Tables\Columns\TextColumn::make('promote_name')
                ->searchable()
                ->label('推广名称'),
            Tables\Columns\TextColumn::make('game_id')
                ->label('游戏ID'),
            Tables\Columns\TextColumn::make('game.name')
                ->label('游戏名称'),
            Tables\Columns\TextColumn::make('promote_type')
                ->badge()
                ->label('类型'),
            Tables\Columns\IconColumn::make('status')
                ->boolean()
                ->label('状态'),
            Tables\Columns\TextColumn::make('user_attributions_count')
                ->label('注册人数')
                ->sortable(),
            Tables\Columns\TextColumn::make('landing_title')
                ->label('落地页标题')
                ->toggleable(isToggledHiddenByDefault: true)
                ->limit(30),
            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->label('创建时间'),
        ])->defaultSort('id', 'desc')
        ->modifyQueryUsing(fn(Builder $query) => $query->withCount('userAttributions'))
        ->filters([
            Tables\Filters\TernaryFilter::make('status')
                ->label('状态')
                ->trueLabel('启用')
                ->falseLabel('停用'),
        ])->actions([
            Tables\Actions\Action::make('toggle_status')
                ->label(fn(Promote $record): string => $record->status ? '停用' : '启用')
                ->icon(fn(Promote $record): string => $record->status ? 'heroicon-o-pause' : 'heroicon-o-play')
                ->action(fn(Promote $record) => $record->update(['status' => !$record->status]))
                ->requiresConfirmation()
                ->color(fn(Promote $record): string => $record->status ? 'warning' : 'success'),
            Tables\Actions\Action::make('preview_landing')
                ->label('预览')
                ->icon('heroicon-o-eye')
                ->color('success')
                ->url(fn(Promote $record): string => url("/p/{$record->promote_code}"))
                ->openUrlInNewTab(),
            Tables\Actions\Action::make('view_links')
                ->label('推广链接')
                ->icon('heroicon-o-link')
                ->color('info')
                ->modalHeading('推广链接')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('关闭')
                ->modalContent(function (Promote $record) {
                    $landingUrl = $record->landing_url;
                    $pathUrl = $record->url_path;
                    $paramUrl = $record->url_param;
                    return view('filament.resources.promote-links', [
                        'landingUrl' => $landingUrl,
                        'pathUrl' => $pathUrl,
                        'paramUrl' => $paramUrl,
                        'promoteCode' => $record->promote_code,
                    ]);
                }),
            Tables\Actions\EditAction::make(),
        ])->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromotes::route('/'),
            'create' => Pages\CreatePromote::route('/create'),
            'edit' => Pages\EditPromote::route('/{record}/edit'),
        ];
    }
}
