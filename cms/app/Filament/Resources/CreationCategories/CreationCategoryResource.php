<?php

namespace App\Filament\Resources\CreationCategories;

use App\Enums\ContentStatus;
use App\Filament\Resources\Concerns\AuthorizesCmsResource;
use App\Filament\Resources\CreationCategories\Pages\ManageCreationCategories;
use App\Models\CreationCategory;
use App\Services\Content\CatalogWorkflow;
use App\Support\CmsOptions;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
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
                TextInput::make('title')->label('Titre')->required()->minLength(3)->maxLength(80),
                TextInput::make('slug')->label('Identifiant URL')->required()->alphaDash()->minLength(3)->maxLength(100)->unique(ignoreRecord: true),
                Textarea::make('description')->label('Description')->required()->minLength(30)->maxLength(500)->rows(4)->columnSpanFull(),
                Select::make('cover_id')->label('Image de couverture')->relationship('cover', 'original_name')->searchable()->preload(),
                TextInput::make('position')->numeric()->minValue(0)->default(0)->required(),
                Toggle::make('is_visible')->label('Visible')->default(true),
                Toggle::make('is_featured')->label('Mise en avant'),
            ]),
            Section::make('Galerie')
                ->description('Ajoutez les photos, complétez leur texte alternatif puis réordonnez-les par glisser-déposer.')
                ->schema([
                    Repeater::make('galleryItems')
                        ->label('Photos')
                        ->relationship()
                        ->orderColumn('position')
                        ->maxItems(200)
                        ->addActionLabel('Ajouter une photo')
                        ->reorderableWithDragAndDrop()
                        ->schema([
                            Select::make('media_asset_id')
                                ->label('Image')
                                ->relationship('mediaAsset', 'original_name')
                                ->searchable()
                                ->preload()
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->required(),
                            TextInput::make('alt_text')
                                ->label('Texte alternatif dans cette galerie')
                                ->maxLength(180)
                                ->helperText('Laissez vide uniquement si le texte alternatif général du média convient.'),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ]),
            Section::make('Publication et référencement')->columns(3)->schema([
                Select::make('status')->label('Statut')->options(CmsOptions::statuses())->default(ContentStatus::Draft->value)->disabled()->dehydrated(),
                DateTimePicker::make('scheduled_at')->label('Publication programmée')->seconds(false),
                DateTimePicker::make('expires_at')->label('Fin de visibilité')->seconds(false)->after('scheduled_at'),
                TextInput::make('seo_title')->label('Titre SEO')->maxLength(80),
                Textarea::make('seo_description')->label('Description SEO')->maxLength(200)->rows(3)->columnSpan(2),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('position')->reorderable('position')->columns([
            ImageColumn::make('cover.path')
                ->label('Couverture')
                ->disk('local')
                ->visibility('private')
                ->square()
                ->imageSize(64),
            TextColumn::make('title')->label('Univers')->searchable()->weight('semibold'),
            TextColumn::make('media_count')->counts('media')->label('Photos'),
            TextColumn::make('status')->label('Statut')->badge()->formatStateUsing(fn ($state): string => CmsOptions::statuses()[$state->value ?? $state] ?? (string) $state),
            TextColumn::make('position')->label('Position')->sortable(),
            IconColumn::make('is_visible')->label('Visible')->boolean(),
        ])->filters([
            TernaryFilter::make('is_visible')->label('Visibilité'),
        ])->recordActions([EditAction::make(), ...self::workflowActions(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageCreationCategories::route('/')];
    }

    /** @return list<Action> */
    private static function workflowActions(): array
    {
        return [
            self::workflowAction('submit', 'Soumettre', ContentStatus::InReview, 'creation-categories.update'),
            self::workflowAction('approve', 'Valider', ContentStatus::Approved, 'creation-categories.publish'),
            self::workflowAction('schedule', 'Programmer', ContentStatus::Scheduled, 'creation-categories.publish'),
            self::workflowAction('publish', 'Publier', ContentStatus::Published, 'creation-categories.publish'),
            self::workflowAction('hide', 'Masquer', ContentStatus::Hidden, 'creation-categories.publish'),
            self::workflowAction('draft', 'Brouillon', ContentStatus::Draft, 'creation-categories.update'),
            self::workflowAction('archive', 'Archiver', ContentStatus::Archived, 'creation-categories.publish'),
        ];
    }

    private static function workflowAction(string $name, string $label, ContentStatus $target, string $permission): Action
    {
        return Action::make($name)->label($label)
            ->visible(fn (CreationCategory $record): bool => (auth()->user()?->can($permission) ?? false)
                && in_array($target, app(CatalogWorkflow::class)->allowedTargets($record), true))
            ->requiresConfirmation()
            ->action(function (CreationCategory $record) use ($target): void {
                app(CatalogWorkflow::class)->transition($record, $target);
                Notification::make()->title('Statut mis à jour')->success()->send();
            });
    }
}
