<?php
namespace App\Filament\Resources\AdminUserResource\Pages;

use App\Filament\Resources\AdminUserResource;
use App\Models\AdminAuditLog;
use Filament\Resources\Pages\CreateRecord;

class CreateAdminUser extends CreateRecord
{
    protected static string $resource = AdminUserResource::class;

    protected function afterCreate(): void
    {
        $data = $this->record->toArray();
        unset($data['password']);
        AdminAuditLog::record('create', 'admin_user', (string)$this->record->id,
            null, $data,
            '创建后台账号'
        );
    }
}
