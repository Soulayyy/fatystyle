<?php

namespace App\Filament\Resources\CreationCategories;

use App\Filament\Resources\Concerns\AuthorizesCmsResource;
use App\Filament\Resources\CreationCategories\Pages\ManageCreationCategories;
use App\Models\CreationCategory;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CreationCategoryResource extends Resource
{
    use AuthorizesCmsResource;

    protected const PERMISSION_MODULE = 'creation-categories';

    protected static ?string $model = CreationCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static ?string $navigationLabel = 'Univers de création';

    protected static ?string $modelLabel = 'univers';

    protected static ?string $pluralModelLabel = 'univers de création';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Présentation')->columns(2)->schema([
                TextInput::make('title')->label('Titre')->required()->maxLength(120),
                TextInput::make('slug')->label('Identifiant URL')->required()->alphaDash()->maxLength(120)->unique(ignoreRecord: true),
                Textarea::make('description')->label('Description')->rows(4)->columnSpanFull(),
                Select::make('cover_id')->label('Image de couverture')->relationship('cover', 'original_name')->searchable()->preload(),
                TextInput::make('position')->numeric()->minValue(0)->default(0)->required(),
                Toggle::make('is_visible')->label('Visible')->default(true),
            ]),
            Section::make('Galerie')->description('Sélectionnez les images appartenant à cet univers.')->schema([
                Select::make('media')
                    ->label('Images')
                    ->relationship('media', 'original_name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('position')->reorderable('position')->columns([
            TextColumn::make('title')->label('Univers')->searchable()->weight('semibold'),
            TextColumn::make('media_count')->counts('media')->label('Photos'),
            TextColumn::make('position')->label('Position')->sortable(),
            IconColumn::make('is_visible')->label('Visible')->boolean(),
        ])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCreationCategories::route('/')];
    }
}
