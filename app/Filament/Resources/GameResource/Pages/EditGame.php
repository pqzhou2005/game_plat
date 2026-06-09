<?php
namespace App\Filament\Resources\GameResource\Pages;

use App\Filament\Resources\GameResource;
use App\Models\AdminAuditLog;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGame extends EditRecord
{
    protected static string $resource = GameResource::class;

    private ?array $originalData = null;

    protected function beforeSave(): void
    {
        $this->originalData = $this->record->getOriginal();
    }

    protected function afterSave(): void
    {
        AdminAuditLog::record('update', 'game', (string)$this->record->id,
            $this->originalData, $this->record->toArray(),
            '修改游戏'
        );
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
