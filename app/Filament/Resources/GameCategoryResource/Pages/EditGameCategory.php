<?php
namespace App\Filament\Resources\GameCategoryResource\Pages;

use App\Filament\Resources\GameCategoryResource;
use App\Models\AdminAuditLog;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGameCategory extends EditRecord
{
    protected static string $resource = GameCategoryResource::class;

    private ?array $originalData = null;

    protected function beforeSave(): void
    {
        $this->originalData = $this->record->getOriginal();
    }

    protected function afterSave(): void
    {
        AdminAuditLog::record('update', 'game_category', (string)$this->record->id,
            $this->originalData, $this->record->toArray(),
            '修改游戏分类'
        );
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
