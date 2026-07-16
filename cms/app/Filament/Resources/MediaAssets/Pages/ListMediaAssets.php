<?php

namespace App\Filament\Resources\MediaAssets\Pages;

use App\Filament\Resources\MediaAssets\MediaAssetResource;
use App\Services\Media\MediaIngestor;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Throwable;

class ListMediaAssets extends ListRecords
{
    protected static string $resource = MediaAssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('bulkUpload')
                ->label('Importer plusieurs images')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->schema([
                    FileUpload::make('files')
                        ->label('Images')
                        ->multiple()
                        ->maxFiles(50)
                        ->disk('local')
                        ->directory('media/originals/uploads')
                        ->visibility('private')
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif', 'image/gif'])
                        ->maxSize(config('cms.media.max_upload_mb', 20) * 1024)
                        ->storeFileNamesIn('original_names')
                        ->required(),
                ])
                ->action(function (array $data, MediaIngestor $ingestor): void {
                    $paths = is_array($data['files'] ?? null) ? $data['files'] : [];
                    $names = is_array($data['original_names'] ?? null) ? $data['original_names'] : [];
                    $imported = 0;
                    $errors = [];

                    foreach ($paths as $key => $path) {
                        try {
                            $ingestor->ingestStoredUpload(
                                temporaryPath: $path,
                                originalName: $names[$key] ?? basename($path),
                                userId: auth()->id(),
                            );
                            $imported++;
                        } catch (Throwable $exception) {
                            report($exception);
                            $errors[] = basename($path);
                        }
                    }

                    Notification::make()
                        ->title("{$imported} image(s) traitée(s)")
                        ->body($errors === [] ? null : 'Échec : '.implode(', ', $errors))
                        ->status($errors === [] ? 'success' : 'warning')
                        ->send();
                }),
            CreateAction::make()->label('Ajouter une image'),
        ];
    }
}
