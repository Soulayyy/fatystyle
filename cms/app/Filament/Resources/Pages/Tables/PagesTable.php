<?php

namespace App\Filament\Resources\Pages\Tables;

use App\Support\CmsOptions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('translations.title')
                    ->label('Titre')
                    ->searchable()
                    ->weight('semibold'),
                TextColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn ($state): string => CmsOptions::statuses()[$state->value ?? $state] ?? (string) $state)
                    ->badge(),
                TextColumn::make('template')
                    ->label('Modèle')
                    ->formatStateUsing(fn ($state): string => CmsOptions::templates()[$state->value ?? $state] ?? (string) $state),
                IconColumn::make('is_home')->label('Accueil')->boolean(),
                TextColumn::make('updated_at')->label('Modifiée')->since()->sortable(),
                TextColumn::make('published_at')->label('Publiée')->dateTime('d/m/Y H:i')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(CmsOptions::statuses()),
                TrashedFilter::make(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
