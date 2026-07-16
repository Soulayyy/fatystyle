<?php

namespace App\Filament\Resources\MediaAssets\Pages;

use App\Filament\Resources\MediaAssets\MediaAssetResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditMediaAsset extends EditRecord
{
    protected static string $resource = MediaAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->disabled(fn (): bool => $this->record->isInUse())
                ->tooltip(fn (): ?string => $this->record->isInUse()
                    ? 'Ce média est utilisé. Retirez-le d’abord de tous les contenus.'
                    : null),
            RestoreAction::make(),
        ];
    }
}
