<?php

namespace App\Filament\Resources\Services;

use App\Filament\Resources\Concerns\AuthorizesCmsResource;
use App\Filament\Resources\Services\Pages\ManageServices;
use App\Models\Service;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServiceResource extends Resource
{
    use AuthorizesCmsResource;

    protected const PERMISSION_MODULE = 'services';

    protected static ?string $model = Service::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $navigationLabel = 'Prestations';

    protected static ?string $modelLabel = 'prestation';

    protected static ?string $pluralModelLabel = 'prestations';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Titre')->required()->maxLength(120),
            TextInput::make('slug')->label('Identifiant URL')->required()->alphaDash()->maxLength(120)->unique(ignoreRecord: true),
            Textarea::make('description')->label('Description')->rows(4)->columnSpanFull(),
            Select::make('image_id')->label('Image')->relationship('image', 'original_name')->searchable()->preload(),
            TextInput::make('position')->numeric()->minValue(0)->default(0)->required(),
            Toggle::make('is_visible')->label('Visible')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('position')->reorderable('position')->columns([
            TextColumn::make('title')->label('Titre')->searchable()->weight('semibold'),
            TextColumn::make('slug')->label('Identifiant'),
            TextColumn::make('position')->label('Position')->sortable(),
            IconColumn::make('is_visible')->label('Visible')->boolean(),
        ])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageServices::route('/')];
    }
}
