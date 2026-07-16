<?php

namespace App\Filament\Resources\CreationCategories\Pages;

use App\Filament\Resources\CreationCategories\CreationCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCreationCategories extends ManageRecords
{
    protected static string $resource = CreationCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nouvel univers')];
    }
}
