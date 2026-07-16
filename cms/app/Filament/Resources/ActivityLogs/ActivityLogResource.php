<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Journal d’audit';

    protected static ?string $modelLabel = 'événement';

    protected static ?string $pluralModelLabel = 'journal d’audit';

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->columns([
            TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i:s')->sortable(),
            TextColumn::make('log_name')->label('Domaine')->badge(),
            TextColumn::make('event')->label('Action')->badge(),
            TextColumn::make('description')->label('Description')->limit(60)->searchable(),
            TextColumn::make('subject_type')->label('Objet')->formatStateUsing(
                fn (?string $state): string => $state ? class_basename($state) : '—',
            ),
            TextColumn::make('subject_id')->label('Identifiant')->limit(18)->copyable(),
            TextColumn::make('causer.name')->label('Utilisateur')->placeholder('Système'),
        ])->filters([
            SelectFilter::make('log_name')->label('Domaine')->options([
                'content' => 'Contenu', 'security' => 'Sécurité', 'default' => 'Général',
            ]),
        ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('audit-log.view') ?? false;
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
        return ['index' => ListActivityLogs::route('/')];
    }
}
