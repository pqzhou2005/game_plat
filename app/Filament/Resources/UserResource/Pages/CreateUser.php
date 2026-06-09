<?php
namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\AdminAuditLog;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        AdminAuditLog::record('create', 'user', (string)$this->record->id,
            null, $this->record->toArray(),
            '创建玩家用户'
        );
    }
}
