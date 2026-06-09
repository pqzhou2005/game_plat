<?php
namespace App\Filament\Resources\GameSsoConfigResource\Pages;

use App\Filament\Resources\GameSsoConfigResource;
use App\Models\AdminAuditLog;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGameSsoConfig extends EditRecord
{
    protected static string $resource = GameSsoConfigResource::class;

    private ?array $originalData = null;

    protected function beforeSave(): void
    {
        $this->originalData = $this->record->getOriginal();
    }

    protected function afterSave(): void
    {
        AdminAuditLog::record('update_sso', 'game_sso_config', (string)$this->record->id,
            $this->maskKeys($this->originalData),
            $this->maskKeys($this->record->toArray()),
            '修改游戏SSO配置'
        );
    }

    private function maskKeys(?array $data): ?array
    {
        if ($data === null) return null;
        $masked = ['login_key', 'pay_key'];
        foreach ($data as $k => $v) {
            if (in_array($k, $masked) && is_string($v) && strlen($v) > 8) {
                $data[$k] = substr($v, 0, 6) . '****' . substr($v, -4);
            }
        }
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
