<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Enums\ContentStatus;
use App\Filament\Resources\Pages\PageResource;
use App\Services\Content\PageVersionService;
use Filament\Resources\Pages\CreateRecord;

class CreatePage extends CreateRecord
{
    protected static string $resource = PageResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['_lock_version']);
        $data['status'] = ContentStatus::Draft;
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        app(PageVersionService::class)->capture($this->record, 'Création de la page');
    }
}
