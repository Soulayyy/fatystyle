<?php

namespace App\Filament\Resources\Services;

use App\Enums\ContentStatus;
use App\Filament\Resources\Concerns\AuthorizesCmsResource;
use App\Filament\Resources\Services\Pages\ManageServices;
use App\Models\Service;
use App\Services\Content\CatalogWorkflow;
use App\Support\CmsOptions;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServiceResource extends Resource
{
    use AuthorizesCmsResource;

    protected const PERMISSION_MODULE = 'services';

    protected static ?string $model = Service::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?string $navigationLabel = 'Prestations';

    protected static ?string $modelLabel = 'prestation';

    protected static ?string $pluralModelLabel = 'prestations';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Prestation')->columns(2)->schema([
                TextInput::make('title')->label('Titre')->required()->minLength(3)->maxLength(80),
                TextInput::make('slug')->label('Identifiant URL')->required()->alphaDash()->minLength(3)->maxLength(100)->unique(ignoreRecord: true),
                Textarea::make('description')->label('Description')->required()->minLength(30)->maxLength(250)->rows(4)->columnSpanFull(),
                Select::make('image_id')->label('Image')->relationship('image', 'original_name')->searchable()->preload(),
                TextInput::make('audience')->label('Public concerné')->maxLength(160),
                TextInput::make('price_label')->label('Tarif indicatif')->maxLength(120),
                TextInput::make('duration_label')->label('Durée indicative')->maxLength(120),
                TextInput::make('cta_label')->label('Libellé du bouton')->maxLength(80),
                TextInput::make('cta_url')->label('Lien du bouton')->maxLength(2048),
                TextInput::make('position')->numeric()->minValue(0)->default(0)->required(),
                Toggle::make('is_featured')->label('Mise en avant'),
            ]),
            Section::make('Publication')->columns(3)->schema([
                Select::make('status')->label('Statut')->options(CmsOptions::statuses())->default(ContentStatus::Draft->value)->disabled()->dehydrated(),
                DateTimePicker::make('scheduled_at')->label('Publication programmée')->seconds(false),
                DateTimePicker::make('expires_at')->label('Fin de visibilité')->seconds(false)->after('scheduled_at'),
                Toggle::make('is_visible')->label('Visible')->default(true),
            ]),
            Section::make('Référencement')->columns(2)->schema([
                TextInput::make('seo_title')->label('Titre SEO')->maxLength(80),
                Textarea::make('seo_description')->label('Description SEO')->maxLength(200)->rows(3),
            ]),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('position')->reorderable('position')->columns([
            TextColumn::make('title')->label('Titre')->searchable()->weight('semibold'),
            TextColumn::make('slug')->label('Identifiant'),
            TextColumn::make('status')->label('Statut')->badge()->formatStateUsing(fn ($state): string => CmsOptions::statuses()[$state->value ?? $state] ?? (string) $state),
            TextColumn::make('position')->label('Position')->sortable(),
            IconColumn::make('is_visible')->label('Visible')->boolean(),
        ])->recordActions([
            EditAction::make(),
            ...self::workflowActions(),
            DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageServices::route('/')];
    }

    /** @return list<Action> */
    private static function workflowActions(): array
    {
        return [
            self::workflowAction('submit', 'Soumettre', ContentStatus::InReview, 'services.update'),
            self::workflowAction('approve', 'Valider', ContentStatus::Approved, 'services.publish'),
            self::workflowAction('schedule', 'Programmer', ContentStatus::Scheduled, 'services.publish'),
            self::workflowAction('publish', 'Publier', ContentStatus::Published, 'services.publish'),
            self::workflowAction('hide', 'Masquer', ContentStatus::Hidden, 'services.publish'),
            self::workflowAction('draft', 'Brouillon', ContentStatus::Draft, 'services.update'),
            self::workflowAction('archive', 'Archiver', ContentStatus::Archived, 'services.publish'),
        ];
    }

    private static function workflowAction(string $name, string $label, ContentStatus $target, string $permission): Action
    {
        return Action::make($name)->label($label)
            ->visible(fn (Service $record): bool => (auth()->user()?->can($permission) ?? false)
                && in_array($target, app(CatalogWorkflow::class)->allowedTargets($record), true))
            ->requiresConfirmation()
            ->action(function (Service $record) use ($target): void {
                app(CatalogWorkflow::class)->transition($record, $target);
                Notification::make()->title('Statut mis à jour')->success()->send();
            });
    }
}
