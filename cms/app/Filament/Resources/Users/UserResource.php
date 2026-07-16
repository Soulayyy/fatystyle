<?php

namespace App\Filament\Resources\Users;

use App\Enums\RoleName;
use App\Filament\Resources\Concerns\AuthorizesCmsResource;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    use AuthorizesCmsResource;

    protected const PERMISSION_MODULE = 'users';

    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Utilisateurs';

    protected static ?string $modelLabel = 'utilisateur';

    protected static ?string $pluralModelLabel = 'utilisateurs';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Compte')->columns(2)->schema([
                TextInput::make('name')->label('Nom')->required()->maxLength(120),
                TextInput::make('email')->label('Email')->email()->required()->maxLength(254)->unique(ignoreRecord: true),
                TextInput::make('password')->label('Mot de passe')
                    ->password()->revealable()->minLength(14)->maxLength(255)
                    ->required(fn (?User $record): bool => $record === null)
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText('14 caractères minimum. Laisser vide pour conserver le mot de passe actuel.'),
                Select::make('roles')->label('Rôles')->relationship('roles', 'name')
                    ->multiple()->preload()->searchable()->required(),
                Select::make('locale')->label('Langue')->options(['fr' => 'Français'])->default('fr')->required(),
                Select::make('timezone')->label('Fuseau horaire')->options(['Europe/Paris' => 'Europe/Paris'])->default('Europe/Paris')->required(),
                Toggle::make('is_active')->label('Compte actif')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('name')->columns([
            TextColumn::make('name')->label('Nom')->searchable()->weight('semibold'),
            TextColumn::make('email')->label('Email')->searchable()->copyable(),
            TextColumn::make('roles.name')->label('Rôles')->badge()
                ->formatStateUsing(fn (string $state): string => self::roleLabels()[$state] ?? $state),
            IconColumn::make('is_active')->label('Actif')->boolean(),
            IconColumn::make('app_authentication_secret')->label('2FA')->boolean(),
            TextColumn::make('last_login_at')->label('Dernière connexion')->since()->placeholder('Jamais'),
        ])->filters([TrashedFilter::make()])->recordActions([EditAction::make()]);
    }

    public static function canDelete(Model $record): bool
    {
        return parent::canDelete($record) && $record->getKey() !== auth()->id();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /** @return array<string, string> */
    private static function roleLabels(): array
    {
        return [
            RoleName::SuperAdministrator->value => 'Super-administrateur',
            RoleName::ContentAdministrator->value => 'Administrateur de contenu',
            RoleName::Editor->value => 'Éditeur',
            RoleName::Validator->value => 'Validateur',
            RoleName::Auditor->value => 'Auditeur',
        ];
    }
}
