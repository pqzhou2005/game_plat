<?php
namespace App\Filament\Resources\PromoteResource\Pages;

use App\Filament\Resources\PromoteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPromote extends EditRecord
{
    protected static string $resource = PromoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('toggle_status')
                ->label(fn($record): string => $record->status ? '停用' : '启用')
                ->icon(fn($record): string => $record->status ? 'heroicon-o-pause' : 'heroicon-o-play')
                ->action(fn($record) => $record->update(['status' => !$record->status]))
                ->requiresConfirmation()
                ->color(fn($record): string => $record->status ? 'warning' : 'success'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
