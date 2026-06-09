<?php
namespace App\Filament\Resources\PaymentConfigResource\Pages;

use App\Filament\Resources\PaymentConfigResource;
use App\Models\AdminAuditLog;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentConfig extends CreateRecord
{
    protected static string $resource = PaymentConfigResource::class;

    protected function afterCreate(): void
    {
        AdminAuditLog::record('create', 'payment_config', (string)$this->record->id,
            null, $this->record->toArray(),
            '创建支付渠道配置'
        );
    }
}
