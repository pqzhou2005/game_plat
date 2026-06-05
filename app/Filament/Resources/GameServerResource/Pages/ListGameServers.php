<?php
namespace App\Filament\Resources\GameServerResource\Pages;

use App\Filament\Resources\GameServerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGameServers extends ListRecords
{
    protected static string $resource = GameServerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
