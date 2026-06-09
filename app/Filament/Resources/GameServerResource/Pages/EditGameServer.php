<?php
namespace App\Filament\Resources\GameServerResource\Pages;

use App\Filament\Resources\GameServerResource;
use App\Models\AdminAuditLog;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGameServer extends EditRecord
{
    protected static string $resource = GameServerResource::class;

    private ?array $originalData = null;

    protected function beforeSave(): void
    {
        $this->originalData = $this->record->getOriginal();
    }

    protected function afterSave(): void
    {
        AdminAuditLog::record('update', 'game_server', (string)$this->record->id,
            $this->originalData, $this->record->toArray(),
            '修改区服'
        );
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
