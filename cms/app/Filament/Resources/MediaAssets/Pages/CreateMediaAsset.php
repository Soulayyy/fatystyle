<?php

namespace App\Filament\Resources\MediaAssets\Pages;

use App\Filament\Resources\MediaAssets\MediaAssetResource;
use App\Services\Media\MediaIngestor;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class CreateMediaAsset extends CreateRecord
{
    protected static string $resource = MediaAssetResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $path = $data['path'] ?? null;
        if (! is_string($path)) {
            throw new RuntimeException('Le fichier envoyé est introuvable.');
        }

        $originalName = $data['uploaded_original_name'] ?? basename($path);
        unset($data['path'], $data['uploaded_original_name']);

        return app(MediaIngestor::class)->ingestStoredUpload(
            temporaryPath: $path,
            originalName: is_string($originalName) ? $originalName : basename($path),
            userId: auth()->id(),
            attributes: $data,
        );
    }
}
