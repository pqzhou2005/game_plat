<?php
namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\AdminAuditLog;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    private ?array $originalData = null;

    protected function beforeSave(): void
    {
        $this->originalData = $this->record->getOriginal();
    }

    protected function afterSave(): void
    {
        $before = $this->maskIdCard($this->originalData);
        $after = $this->maskIdCard($this->record->toArray());
        AdminAuditLog::record('update_user', 'user', (string)$this->record->id,
            $before, $after, '修改用户信息'
        );
    }

    private function maskIdCard(?array $data): ?array
    {
        if ($data === null) return null;
        if (isset($data['id_card']) && is_string($data['id_card']) && strlen($data['id_card']) >= 10) {
            $data['id_card'] = substr($data['id_card'], 0, 6) . '********' . substr($data['id_card'], -4);
        }
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
