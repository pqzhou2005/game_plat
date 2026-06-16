<?php
namespace App\Filament\Resources\PromoteResource\Pages;

use App\Filament\Resources\PromoteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPromotes extends ListRecords
{
    protected static string $resource = PromoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('新建推广入口'),
        ];
    }
}
