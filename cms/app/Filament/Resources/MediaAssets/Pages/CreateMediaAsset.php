<?php

namespace App\Filament\Resources\MediaAssets\Pages;

use App\Filament\Resources\MediaAssets\MediaAssetResource;
use App\Models\MediaAsset;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CreateMediaAsset extends CreateRecord
{
    protected static string $resource = MediaAssetResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $path = $data['path'] ?? null;
        if (! is_string($path) || ! Storage::disk('local')->exists($path)) {
            throw new RuntimeException('Le fichier envoyé est introuvable.');
        }

        $absolutePath = Storage::disk('local')->path($path);
        $dimensions = @getimagesize($absolutePath);
        $hash = hash_file('sha256', $absolutePath);

        if (MediaAsset::withTrashed()->where('sha256', $hash)->exists()) {
            Storage::disk('local')->delete($path);
            throw ValidationException::withMessages([
                'data.path' => 'Cette image existe déjà dans la médiathèque.',
            ]);
        }

        return [
            ...$data,
            'disk' => 'local',
            'original_name' => basename($path),
            'mime_type' => mime_content_type($absolutePath) ?: 'application/octet-stream',
            'extension' => strtolower(pathinfo($path, PATHINFO_EXTENSION)) ?: null,
            'size_bytes' => filesize($absolutePath),
            'width' => $dimensions === false ? null : $dimensions[0],
            'height' => $dimensions === false ? null : $dimensions[1],
            'sha256' => $hash,
            'uploaded_by' => auth()->id(),
        ];
    }
}
