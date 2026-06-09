<?php
namespace App\Filament\Resources\RecommendationResource\Pages;

use App\Filament\Resources\RecommendationResource;
use App\Models\AdminAuditLog;
use Filament\Resources\Pages\CreateRecord;

class CreateRecommendation extends CreateRecord
{
    protected static string $resource = RecommendationResource::class;

    protected function afterCreate(): void
    {
        AdminAuditLog::record('create', 'recommendation', (string)$this->record->id,
            null, $this->record->toArray(),
            '创建推荐位'
        );
    }
}
