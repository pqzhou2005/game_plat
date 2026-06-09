<?php namespace App\Filament\Resources\NoticeResource\Pages;

use App\Filament\Resources\NoticeResource;
use App\Models\AdminAuditLog;
use Filament\Actions; use Filament\Resources\Pages\EditRecord;

class EditNotice extends EditRecord
{
    protected static string $resource = NoticeResource::class;

    private ?array $originalData = null;

    protected function beforeSave(): void
    {
        $this->originalData = $this->record->getOriginal();
    }

    protected function afterSave(): void
    {
        AdminAuditLog::record('update', 'notice', (string)$this->record->id,
            $this->originalData, $this->record->toArray(),
            '修改公告'
        );
    }

    protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; }
}
