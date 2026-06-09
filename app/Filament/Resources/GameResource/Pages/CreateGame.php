<?php
namespace App\Filament\Resources\GameResource\Pages;

use App\Filament\Resources\GameResource;
use App\Models\AdminAuditLog;
use Filament\Resources\Pages\CreateRecord;

class CreateGame extends CreateRecord
{
    protected static string $resource = GameResource::class;

    protected function afterCreate(): void
    {
        AdminAuditLog::record('create', 'game', (string)$this->record->id,
            null, $this->record->toArray(),
            '创建游戏'
        );
    }
}
