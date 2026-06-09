<?php
namespace App\Filament\Resources\PaymentOrderResource\Pages;

use App\Filament\Resources\PaymentOrderResource;
use App\Models\AdminAuditLog;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaymentOrder extends EditRecord
{
    protected static string $resource = PaymentOrderResource::class;

    private ?array $originalData = null;

    protected function beforeSave(): void
    {
        $this->originalData = $this->record->getOriginal();
    }

    protected function afterSave(): void
    {
        AdminAuditLog::record('update', 'payment_order', (string)$this->record->id,
            $this->originalData, $this->record->toArray(),
            '修改订单'
        );
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
