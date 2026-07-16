<?php

namespace App\Filament\Resources\MediaAssets\Pages;

use App\Filament\Resources\MediaAssets\MediaAssetResource;
use App\Services\Media\MediaVariantGenerator;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditMediaAsset extends EditRecord
{
    protected static string $resource = MediaAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('regenerateVariants')
                ->label('Régénérer les variantes')
                ->icon(Heroicon::OutlinedArrowPath)
                ->requiresConfirmation()
                ->action(function (MediaVariantGenerator $generator): void {
                    $generator->generate($this->record, force: true);
                    Notification::make()->title('Variantes responsives régénérées')->success()->send();
                }),
            DeleteAction::make()
                ->disabled(fn (): bool => $this->record->isInUse())
                ->tooltip(fn (): ?string => $this->record->isInUse()
                    ? 'Ce média est utilisé. Retirez-le d’abord de tous les contenus.'
                    : null),
            RestoreAction::make(),
        ];
    }
}
