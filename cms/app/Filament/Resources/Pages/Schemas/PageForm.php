<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Support\CmsOptions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('_lock_version')->default(1),
            Section::make('Paramètres de la page')
                ->columns(3)
                ->schema([
                    Select::make('template')
                        ->label('Modèle')
                        ->options(CmsOptions::templates())
                        ->required(),
                    Select::make('status')
                        ->label('Statut éditorial')
                        ->options(CmsOptions::statuses())
                        ->disabled()
                        ->dehydrated(false),
                    Toggle::make('is_home')
                        ->label('Page d’accueil'),
                    DateTimePicker::make('scheduled_at')
                        ->label('Publication programmée')
                        ->seconds(false),
                    DateTimePicker::make('expires_at')
                        ->label('Fin de visibilité')
                        ->seconds(false)
                        ->after('scheduled_at'),
                ]),
            Section::make('Contenu et référencement')
                ->description('Une traduction française est requise. Le schéma accepte déjà d’autres langues.')
                ->schema([
                    Repeater::make('translations')
                        ->label('Traductions')
                        ->relationship()
                        ->defaultItems(1)
                        ->minItems(1)
                        ->schema([
                            Select::make('locale')
                                ->label('Langue')
                                ->options(array_combine(config('cms.supported_locales'), config('cms.supported_locales')))
                                ->default(config('cms.default_locale'))
                                ->required(),
                            TextInput::make('slug')
                                ->label('Adresse')
                                ->helperText('Laisser vide uniquement pour la page d’accueil.')
                                ->maxLength(120),
                            TextInput::make('title')->label('Titre navigateur')->required()->maxLength(120),
                            TextInput::make('h1')->label('Titre principal H1')->required()->maxLength(100),
                            Textarea::make('intro')->label('Introduction')->rows(3)->columnSpanFull(),
                            TextInput::make('seo_title')->label('Titre SEO')->maxLength(80),
                            Textarea::make('seo_description')->label('Description SEO')->rows(3)->maxLength(200),
                            TextInput::make('canonical_url')->label('URL canonique')->url()->maxLength(2048),
                            Toggle::make('is_indexable')->label('Indexable')->default(true),
                            Toggle::make('links_followed')->label('Liens suivis')->default(true),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ]),
            Section::make('Blocs de la page')
                ->description('Les blocs peuvent être réordonnés. Le contenu JSON conserve fidèlement les structures complexes.')
                ->schema([
                    Repeater::make('blocks')
                        ->relationship()
                        ->orderColumn('position')
                        ->reorderable()
                        ->collapsible()
                        ->cloneable()
                        ->itemLabel(fn (array $state): string => CmsOptions::blockTypes()[$state['type'] ?? ''] ?? 'Nouveau bloc')
                        ->schema([
                            Select::make('type')
                                ->label('Type')
                                ->options(CmsOptions::blockTypes())
                                ->required(),
                            Toggle::make('is_visible')->label('Visible')->default(true),
                            Toggle::make('is_locked')->label('Verrouillé'),
                            DateTimePicker::make('visible_from')->label('Visible à partir de')->seconds(false),
                            DateTimePicker::make('visible_until')->label('Visible jusqu’au')->seconds(false)->after('visible_from'),
                            Repeater::make('translations')
                                ->relationship()
                                ->label('Contenu traduit')
                                ->defaultItems(1)
                                ->minItems(1)
                                ->schema([
                                    Select::make('locale')
                                        ->label('Langue')
                                        ->options(array_combine(config('cms.supported_locales'), config('cms.supported_locales')))
                                        ->default(config('cms.default_locale'))
                                        ->required(),
                                    Textarea::make('content_json')
                                        ->label('Contenu structuré JSON')
                                        ->rows(12)
                                        ->rule('json')
                                        ->required()
                                        ->columnSpanFull(),
                                ])
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
