<?php
namespace App\Filament\Resources\GameCategoryResource\Pages;

use App\Filament\Resources\GameCategoryResource;
use App\Models\AdminAuditLog;
use Filament\Resources\Pages\CreateRecord;

class CreateGameCategory extends CreateRecord
{
    protected static string $resource = GameCategoryResource::class;

    protected function afterCreate(): void
    {
        AdminAuditLog::record('create', 'game_category', (string)$this->record->id,
            null, $this->record->toArray(),
            '创建游戏分类'
        );
    }
}
