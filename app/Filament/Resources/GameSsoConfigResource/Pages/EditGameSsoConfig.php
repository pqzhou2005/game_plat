<?php
namespace App\Filament\Resources\GameSsoConfigResource\Pages;

use App\Filament\Resources\GameSsoConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGameSsoConfig extends EditRecord
{
    protected static string $resource = GameSsoConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
