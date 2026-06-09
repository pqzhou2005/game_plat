<?php
namespace App\Filament\Resources\AdminUserResource\Pages;

use App\Filament\Resources\AdminUserResource;
use App\Models\AdminAuditLog;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdminUser extends EditRecord
{
    protected static string $resource = AdminUserResource::class;

    private ?array $originalData = null;

    protected function beforeSave(): void
    {
        $this->originalData = $this->record->getOriginal();
    }

    protected function afterSave(): void
    {
        $masked = fn(?array $d) => $d ? array_merge($d, ['password' => '***']) : null;
        AdminAuditLog::record('update_admin_user', 'admin_user', (string)$this->record->id,
            $masked($this->originalData), $masked($this->record->toArray()),
            '修改后台账号'
        );
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
