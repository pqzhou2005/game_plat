<?php
namespace App\Filament\Resources\GameServerResource\Pages;

use App\Filament\Resources\GameServerResource;
use App\Models\AdminAuditLog;
use Filament\Resources\Pages\CreateRecord;

class CreateGameServer extends CreateRecord
{
    protected static string $resource = GameServerResource::class;

    protected function afterCreate(): void
    {
        AdminAuditLog::record('create', 'game_server', (string)$this->record->id,
            null, $this->record->toArray(),
            '创建区服'
        );
    }
}
