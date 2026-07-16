<?php

namespace App\Filament\Resources\SiteSettings;

use App\Filament\Resources\Concerns\AuthorizesCmsResource;
use App\Filament\Resources\SiteSettings\Pages\ManageSiteSettings;
use App\Models\SiteSetting;
use BackedEnum;
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

class SiteSettingResource extends Resource
{
    use AuthorizesCmsResource;

    protected const PERMISSION_MODULE = 'site-settings';

    protected static ?string $model = SiteSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Réglages du site';

    protected static ?string $modelLabel = 'réglage';

    protected static ?string $pluralModelLabel = 'réglages du site';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('group')->label('Groupe')->required()->maxLength(60),
            TextInput::make('key')->label('Clé')->required()->maxLength(100),
            Select::make('locale')->label('Langue')->options(['fr' => 'Français'])->default('fr')->required(),
            Toggle::make('is_public')->label('Exporté vers le site public')->default(true),
            Textarea::make('value_json')->label('Valeur structurée JSON')->rows(18)->rule('json')->required()->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('group')->columns([
            TextColumn::make('group')->label('Groupe')->badge()->sortable(),
            TextColumn::make('key')->label('Clé')->searchable()->weight('semibold'),
            TextColumn::make('locale')->label('Langue'),
            IconColumn::make('is_public')->label('Public')->boolean(),
            TextColumn::make('updated_at')->label('Modifié')->since(),
        ])->recordActions([EditAction::make()]);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('site-settings.manage') ?? false;
    }

    public static function getPages(): array
    {
        return ['index' => ManageSiteSettings::route('/')];
    }
}
