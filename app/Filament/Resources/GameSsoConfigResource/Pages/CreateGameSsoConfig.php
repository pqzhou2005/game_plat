<?php
namespace App\Filament\Resources\GameSsoConfigResource\Pages;

use App\Filament\Resources\GameSsoConfigResource;
use App\Models\AdminAuditLog;
use Filament\Resources\Pages\CreateRecord;

class CreateGameSsoConfig extends CreateRecord
{
    protected static string $resource = GameSsoConfigResource::class;

    protected function afterCreate(): void
    {
        AdminAuditLog::record('create', 'game_sso_config', (string)$this->record->id,
            null, $this->record->toArray(),
            '创建游戏SSO配置'
        );
    }
}
