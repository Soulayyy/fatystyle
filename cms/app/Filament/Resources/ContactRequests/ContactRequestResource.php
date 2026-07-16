<?php

namespace App\Filament\Resources\ContactRequests;

use App\Enums\ContactStatus;
use App\Filament\Resources\Concerns\AuthorizesCmsResource;
use App\Filament\Resources\ContactRequests\Pages\ManageContactRequests;
use App\Models\ContactRequest;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ContactRequestResource extends Resource
{
    use AuthorizesCmsResource;

    protected const PERMISSION_MODULE = 'contacts';

    protected static ?string $model = ContactRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $navigationLabel = 'Demandes de contact';

    protected static ?string $modelLabel = 'demande';

    protected static ?string $pluralModelLabel = 'demandes de contact';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Demande')->columns(2)->schema([
                TextInput::make('reference')->label('Référence')->disabled()->dehydrated(),
                Select::make('status')->label('Statut')->options(self::statusOptions())->required(),
                TextInput::make('name')->label('Nom')->required()->maxLength(120),
                TextInput::make('email')->label('Email')->email()->required()->maxLength(254),
                TextInput::make('phone')->label('Téléphone')->tel()->maxLength(40),
                TextInput::make('request_type')->label('Type de demande')->maxLength(120),
                DatePicker::make('desired_date')->label('Date souhaitée'),
                DateTimePicker::make('received_at')->label('Reçue le')->seconds(false)->required(),
                Textarea::make('message')->label('Message')->rows(8)->required()->columnSpanFull(),
            ]),
            Section::make('Suivi interne')->columns(2)->schema([
                Select::make('assigned_to')->label('Assignée à')->relationship('assignee', 'name')->searchable()->preload(),
                DateTimePicker::make('replied_at')->label('Réponse envoyée le')->seconds(false),
                Textarea::make('internal_notes')->label('Notes internes')->rows(5)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('received_at', 'desc')->columns([
            TextColumn::make('reference')->label('Référence')->searchable()->weight('semibold'),
            TextColumn::make('status')->label('Statut')->badge()
                ->formatStateUsing(fn ($state): string => self::statusOptions()[$state->value ?? $state] ?? (string) $state),
            TextColumn::make('name')->label('Nom')->searchable(),
            TextColumn::make('email')->label('Email')->searchable()->copyable(),
            TextColumn::make('request_type')->label('Demande')->limit(35),
            TextColumn::make('assignee.name')->label('Assignée à')->placeholder('Non assignée'),
            TextColumn::make('received_at')->label('Reçue')->dateTime('d/m/Y H:i')->sortable(),
        ])->filters([
            SelectFilter::make('status')->label('Statut')->options(self::statusOptions()),
            TrashedFilter::make(),
        ])->recordActions([EditAction::make()]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return ['index' => ManageContactRequests::route('/')];
    }

    /** @return array<string, string> */
    private static function statusOptions(): array
    {
        return [
            ContactStatus::New->value => 'Nouvelle',
            ContactStatus::InProgress->value => 'En cours',
            ContactStatus::Waiting->value => 'En attente',
            ContactStatus::Replied->value => 'Réponse envoyée',
            ContactStatus::Closed->value => 'Clôturée',
            ContactStatus::Spam->value => 'Indésirable',
        ];
    }
}
