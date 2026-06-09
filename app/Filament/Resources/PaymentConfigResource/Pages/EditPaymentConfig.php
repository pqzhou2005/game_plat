<?php
namespace App\Filament\Resources\PaymentConfigResource\Pages;

use App\Filament\Resources\PaymentConfigResource;
use App\Models\AdminAuditLog;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaymentConfig extends EditRecord
{
    protected static string $resource = PaymentConfigResource::class;

    private ?array $originalData = null;

    protected function beforeSave(): void
    {
        $this->originalData = $this->record->getOriginal();
    }

    protected function afterSave(): void
    {
        AdminAuditLog::record('update_config', 'payment_config', (string)$this->record->id,
            $this->maskSensitive($this->originalData),
            $this->maskSensitive($this->record->toArray()),
            '修改支付渠道配置'
        );
    }

    private function maskSensitive(?array $data): ?array
    {
        if ($data === null) return null;
        $sensitiveKeys = ['app_secret_cert', 'mch_secret_key', 'secret', 'key', 'password', 'private_key'];
        foreach ($data as $k => $v) {
            if (is_string($v)) {
                foreach ($sensitiveKeys as $sk) {
                    if (str_contains(strtolower($k), $sk) && strlen($v) > 8) {
                        $data[$k] = substr($v, 0, 6) . '****' . substr($v, -4);
                        break;
                    }
                }
            }
            if (is_array($v)) {
                $data[$k] = $this->maskSensitive($v);
            }
        }
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
