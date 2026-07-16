<?php

namespace App\Filament\Resources\MediaAssets;

use App\Filament\Resources\Concerns\AuthorizesCmsResource;
use App\Filament\Resources\MediaAssets\Pages\CreateMediaAsset;
use App\Filament\Resources\MediaAssets\Pages\EditMediaAsset;
use App\Filament\Resources\MediaAssets\Pages\ListMediaAssets;
use App\Models\MediaAsset;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MediaAssetResource extends Resource
{
    use AuthorizesCmsResource;

    protected const PERMISSION_MODULE = 'media';

    protected static ?string $model = MediaAsset::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $navigationLabel = 'Médiathèque';

    protected static ?string $modelLabel = 'média';

    protected static ?string $pluralModelLabel = 'médiathèque';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Fichier original')->schema([
                FileUpload::make('path')
                    ->label('Image')
                    ->disk('local')
                    ->directory('media/originals/uploads')
                    ->visibility('private')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif', 'image/gif'])
                    ->maxSize(config('cms.media.max_upload_mb', 20) * 1024)
                    ->storeFileNamesIn('uploaded_original_name')
                    ->required(fn (?MediaAsset $record): bool => $record === null)
                    ->disabled(fn (?MediaAsset $record): bool => $record !== null)
                    ->dehydrated(fn (?MediaAsset $record): bool => $record === null)
                    ->helperText('JPEG, PNG, WebP, AVIF ou GIF. Taille maximale configurable, 20 Mo par défaut.'),
            ]),
            Section::make('Accessibilité et informations')->columns(2)->schema([
                TextInput::make('title')->label('Titre interne')->maxLength(180),
                Toggle::make('is_decorative')
                    ->label('Image décorative')
                    ->helperText('À activer uniquement si l’image ne transmet aucune information.'),
                TextInput::make('alt_text')
                    ->label('Texte alternatif')
                    ->maxLength(180)
                    ->helperText('Obligatoire avant publication, sauf pour une image décorative.'),
                TextInput::make('credit')->label('Crédit')->maxLength(180),
                TextInput::make('rights')->label('Droits / licence')->maxLength(180),
                DatePicker::make('taken_at')->label('Date de prise de vue'),
                TagsInput::make('tags')->label('Étiquettes')->columnSpanFull(),
                Textarea::make('caption')->label('Légende')->rows(3)->maxLength(500)->columnSpanFull(),
            ]),
            Section::make('Recadrage responsive')
                ->description('Le point focal indique la zone importante de l’image, entre 0 et 1 sur chaque axe.')
                ->columns(2)
                ->collapsed()
                ->schema([
                    TextInput::make('focal_x')->label('Point focal horizontal')->numeric()->minValue(0)->maxValue(1)->step(0.01)->default(0.5),
                    TextInput::make('focal_y')->label('Point focal vertical')->numeric()->minValue(0)->maxValue(1)->step(0.01)->default(0.5),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('path')
                    ->label('Aperçu')
                    ->disk(fn (MediaAsset $record): string => $record->disk)
                    ->visibility('private')
                    ->square()
                    ->imageSize(64),
                TextColumn::make('original_name')->label('Fichier')->searchable()->weight('semibold'),
                TextColumn::make('mime_type')->label('Format')->badge(),
                TextColumn::make('human_size')->label('Poids'),
                TextColumn::make('dimensions')->label('Dimensions')->state(
                    fn (MediaAsset $record): string => $record->width && $record->height ? "{$record->width} × {$record->height}" : '—',
                ),
                TextColumn::make('alt_text')->label('Texte alternatif')->limit(45)->placeholder('À compléter')->searchable(),
                IconColumn::make('is_decorative')->label('Décorative')->boolean(),
                TextColumn::make('usages_count')->counts('usages')->label('Utilisations')->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'gray'),
                TextColumn::make('variants_count')->counts('variants')->label('Variantes')->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'info' : 'warning'),
                TextColumn::make('created_at')->label('Ajouté')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('mime_type')->label('Format')->options([
                    'image/jpeg' => 'JPEG',
                    'image/png' => 'PNG',
                    'image/webp' => 'WebP',
                    'image/gif' => 'GIF',
                ]),
                Filter::make('missing_alt')
                    ->label('Texte alternatif manquant')
                    ->query(fn (Builder $query): Builder => $query->where('is_decorative', false)->where(
                        fn (Builder $query): Builder => $query->whereNull('alt_text')->orWhere('alt_text', ''),
                    )),
                SelectFilter::make('uploaded_by')->label('Auteur')->relationship('uploader', 'name')->searchable()->preload(),
                Filter::make('unused')
                    ->label('Non utilisés')
                    ->query(fn (Builder $query): Builder => $query->whereDoesntHave('usages')),
                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMediaAssets::route('/'),
            'create' => CreateMediaAsset::route('/create'),
            'edit' => EditMediaAsset::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
