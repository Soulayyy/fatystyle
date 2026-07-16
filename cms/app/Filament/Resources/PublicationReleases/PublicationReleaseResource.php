<?php

namespace App\Filament\Resources\PublicationReleases;

use App\Filament\Resources\PublicationReleases\Pages\ListPublicationReleases;
use App\Models\PublicationRelease;
use App\Services\Publishing\ReleasePublisher;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PublicationReleaseResource extends Resource
{
    protected static ?string $model = PublicationRelease::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRocketLaunch;

    protected static ?string $navigationLabel = 'Publications';

    protected static ?string $modelLabel = 'publication';

    protected static ?string $pluralModelLabel = 'publications';

    public static function table(Table $table): Table
    {
        return $table->defaultSort('sequence', 'desc')->columns([
            TextColumn::make('sequence')->label('N°')->numeric()->sortable(),
            TextColumn::make('status')->label('Statut')->badge(),
            TextColumn::make('checksum')->label('Empreinte')->limit(12)->copyable(),
            TextColumn::make('publisher.name')->label('Publiée par')->placeholder('Système'),
            TextColumn::make('published_at')->label('Date')->dateTime('d/m/Y H:i:s')->placeholder('—'),
            TextColumn::make('rollback_of_id')->label('Restauration')->placeholder('—')->limit(12),
        ])->recordActions([
            Action::make('rollback')->label('Restaurer')->icon(Heroicon::OutlinedArrowUturnLeft)
                ->visible(fn (PublicationRelease $record): bool => $record->status === 'published'
                    && (auth()->user()?->can('releases.publish') ?? false))
                ->requiresConfirmation()
                ->action(function (PublicationRelease $record): void {
                    app(ReleasePublisher::class)->rollback($record);
                    Notification::make()->title('Release restaurée')->success()->send();
                }),
        ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('releases.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => ListPublicationReleases::route('/')];
    }
}
