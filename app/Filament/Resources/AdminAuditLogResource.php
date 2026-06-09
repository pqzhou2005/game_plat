<?php
namespace App\Filament\Resources;

use App\Filament\Resources\AdminAuditLogResource\Pages;
use App\Models\AdminAuditLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AdminAuditLogResource extends Resource
{
    protected static ?string $model = AdminAuditLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = '系统管理';
    protected static ?string $navigationLabel = '操作审计';
    protected static ?string $modelLabel = '审计日志';

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('created_at')->label('时间')->dateTime()->sortable(),
            Tables\Columns\TextColumn::make('operator')->label('操作人')->searchable(),
            Tables\Columns\TextColumn::make('action')
                ->label('操作')
                ->badge()
                ->color(fn(string $state): string => match ($state) {
                    'login', 'logout' => 'info',
                    'create' => 'success',
                    'update' => 'warning',
                    'top_up' => 'success',
                    'refund' => 'danger',
                    'retry_notify' => 'warning',
                    'update_config', 'update_sso' => 'info',
                    'update_user', 'update_admin_user' => 'warning',
                    default => 'gray',
                })
                ->formatStateUsing(fn(string $state): string => match ($state) {
                    'login' => '登录',
                    'logout' => '退出',
                    'create' => '创建',
                    'update' => '修改',
                    'top_up' => '手动补单',
                    'refund' => '退款',
                    'retry_notify' => '重试发货',
                    'update_config' => '修改配置',
                    'update_sso' => '修改SSO配置',
                    'update_user' => '修改用户',
                    'update_admin_user' => '修改后台账号',
                    default => $state,
                }),
            Tables\Columns\TextColumn::make('target_type')
                ->label('目标')
                ->formatStateUsing(fn(?string $state): string => match ($state) {
                    'payment_order' => '订单',
                    'payment_config' => '支付配置',
                    'game_sso_config' => 'SSO配置',
                    'game' => '游戏',
                    'game_category' => '游戏分类',
                    'game_server' => '区服',
                    'notice' => '公告',
                    'recommendation' => '推荐位',
                    'user' => '玩家',
                    'admin_user' => '后台账号',
                    default => $state ?? '',
                }),
            Tables\Columns\TextColumn::make('target_id')->label('目标ID'),
            Tables\Columns\TextColumn::make('remark')->label('备注')->limit(40),
            Tables\Columns\TextColumn::make('ip')->label('IP'),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminAuditLogs::route('/'),
        ];
    }
}
