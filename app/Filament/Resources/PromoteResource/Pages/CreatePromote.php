<?php
namespace App\Filament\Resources\PromoteResource\Pages;

use App\Filament\Resources\PromoteResource;
use App\Models\Promote;
use App\Services\PromotionAttributionService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePromote extends CreateRecord
{
    protected static string $resource = PromoteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['promote_code'] = PromotionAttributionService::generatePromoteCode();
        $data['promote_type'] = 'landing';
        $data['status'] = 1;
        $data['created_by'] = Auth::guard('admin')->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        \Filament\Notifications\Notification::make()
            ->title('推广入口创建成功')
            ->body("推广码：{$record->promote_code}")
            ->success()
            ->send();
    }
}
