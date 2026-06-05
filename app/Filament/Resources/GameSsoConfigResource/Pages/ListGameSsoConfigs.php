<?php
namespace App\Filament\Resources\GameSsoConfigResource\Pages;

use App\Filament\Resources\GameSsoConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGameSsoConfigs extends ListRecords
{
    protected static string $resource = GameSsoConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
