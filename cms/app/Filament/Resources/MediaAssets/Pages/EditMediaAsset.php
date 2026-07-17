<?php

namespace App\Filament\Resources\MediaAssets\Pages;

use App\Filament\Resources\MediaAssets\MediaAssetResource;
use App\Services\Media\MediaReplacementService;
use App\Services\Media\MediaVariantGenerator;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditMediaAsset extends EditRecord
{
    protected static string $resource = MediaAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('replace')
                ->label('Remplacer le fichier')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->requiresConfirmation()
                ->schema([
                    FileUpload::make('file')->label('Nouvelle image')->disk('local')->directory('media/originals/uploads')
                        ->visibility('private')->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif', 'image/gif'])
                        ->maxSize(config('cms.media.max_upload_mb', 20) * 1024)->storeFileNamesIn('original_name')->required(),
                ])
                ->action(function (array $data, MediaReplacementService $service): void {
                    $replacement = $service->replace($this->record, $data['file'], $data['original_name'] ?? basename($data['file']), auth()->id());
                    Notification::make()->title('Média remplacé dans toutes ses utilisations')->success()->send();
                    $this->redirect(MediaAssetResource::getUrl('edit', ['record' => $replacement]));
                }),
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
