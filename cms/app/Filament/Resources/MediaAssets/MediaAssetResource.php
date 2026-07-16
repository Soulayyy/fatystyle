<?php

namespace App\Filament\Resources\MediaAssets;

use App\Filament\Resources\Concerns\AuthorizesCmsResource;
use App\Filament\Resources\MediaAssets\Pages\CreateMediaAsset;
use App\Filament\Resources\MediaAssets\Pages\EditMediaAsset;
use App\Filament\Resources\MediaAssets\Pages\ListMediaAssets;
use App\Models\MediaAsset;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
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
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                    ->maxSize(12 * 1024)
                    ->required(fn (?MediaAsset $record): bool => $record === null)
                    ->disabled(fn (?MediaAsset $record): bool => $record !== null)
                    ->dehydrated(fn (?MediaAsset $record): bool => $record === null)
                    ->helperText('JPEG, PNG, WebP ou GIF, 12 Mo maximum.'),
            ]),
            Section::make('Accessibilité et crédits')->columns(2)->schema([
                TextInput::make('alt_text')->label('Texte alternatif')->maxLength(180),
                TextInput::make('credit')->label('Crédit')->maxLength(180),
                Textarea::make('caption')->label('Légende')->rows(3)->maxLength(500)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('original_name')->label('Fichier')->searchable()->weight('semibold'),
                TextColumn::make('mime_type')->label('Format')->badge(),
                TextColumn::make('human_size')->label('Poids'),
                TextColumn::make('dimensions')->label('Dimensions')->state(
                    fn (MediaAsset $record): string => $record->width && $record->height ? "{$record->width} × {$record->height}" : '—',
                ),
                TextColumn::make('alt_text')->label('Texte alternatif')->limit(45)->placeholder('À compléter'),
                TextColumn::make('usages_count')->counts('usages')->label('Utilisations'),
                TextColumn::make('created_at')->label('Ajouté')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([TrashedFilter::make()])
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
