<?php namespace App\Filament\Resources\NoticeResource\Pages;

use App\Filament\Resources\NoticeResource;
use App\Models\AdminAuditLog;
use Filament\Resources\Pages\CreateRecord;

class CreateNotice extends CreateRecord
{
    protected static string $resource = NoticeResource::class;

    protected function afterCreate(): void
    {
        AdminAuditLog::record('create', 'notice', (string)$this->record->id,
            null, $this->record->toArray(),
            '创建公告'
        );
    }
}
