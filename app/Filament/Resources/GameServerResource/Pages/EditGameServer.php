<?php
namespace App\Filament\Resources\GameServerResource\Pages;

use App\Filament\Resources\GameServerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGameServer extends EditRecord
{
    protected static string $resource = GameServerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
