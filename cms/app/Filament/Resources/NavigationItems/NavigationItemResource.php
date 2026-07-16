<?php

namespace App\Filament\Resources\NavigationItems;

use App\Filament\Resources\Concerns\AuthorizesCmsResource;
use App\Filament\Resources\NavigationItems\Pages\ManageNavigationItems;
use App\Models\NavigationItem;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NavigationItemResource extends Resource
{
    use AuthorizesCmsResource;

    protected const PERMISSION_MODULE = 'navigation';

    protected static ?string $model = NavigationItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars3;

    protected static ?string $navigationLabel = 'Navigation';

    protected static ?string $modelLabel = 'lien';

    protected static ?string $pluralModelLabel = 'navigation';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label')->label('Libellé')->required()->maxLength(80),
            TextInput::make('url')->label('URL')->required()->maxLength(2048),
            Select::make('location')->options(['primary' => 'Menu principal', 'footer' => 'Pied de page'])->default('primary')->required(),
            Select::make('locale')->options(['fr' => 'Français'])->default('fr')->required(),
            TextInput::make('position')->label('Position')->numeric()->minValue(0)->default(0)->required(),
            Toggle::make('is_visible')->label('Visible')->default(true),
            Toggle::make('opens_new_tab')->label('Ouvrir dans un nouvel onglet'),
            Select::make('parent_id')->label('Lien parent')->relationship('parent', 'label')->searchable()->preload(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('position')->reorderable('position')->columns([
            TextColumn::make('label')->label('Libellé')->searchable()->weight('semibold'),
            TextColumn::make('url')->label('Destination')->limit(55),
            TextColumn::make('location')->label('Emplacement')->badge(),
            TextColumn::make('position')->label('Position')->sortable(),
            IconColumn::make('is_visible')->label('Visible')->boolean(),
        ])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageNavigationItems::route('/')];
    }
}
