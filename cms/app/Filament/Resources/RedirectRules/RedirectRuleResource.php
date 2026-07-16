<?php

namespace App\Filament\Resources\RedirectRules;

use App\Filament\Resources\Concerns\AuthorizesCmsResource;
use App\Filament\Resources\RedirectRules\Pages\ManageRedirectRules;
use App\Models\RedirectRule;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class RedirectRuleResource extends Resource
{
    use AuthorizesCmsResource;

    protected const PERMISSION_MODULE = 'redirects';

    protected static ?string $model = RedirectRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnRight;

    protected static ?string $navigationLabel = 'Redirections';

    protected static ?string $modelLabel = 'redirection';

    protected static ?string $pluralModelLabel = 'redirections';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('source_path')->label('Ancienne adresse')->placeholder('/ancienne-page.html')
                ->required()->maxLength(2048)->unique(ignoreRecord: true)
                ->rule('regex:/^\/[A-Za-z0-9._~!$&\'()*+,;=:@%\/-]*$/'),
            TextInput::make('target_url')->label('Nouvelle destination')->required()->maxLength(2048),
            Select::make('http_status')->label('Type')->options([
                301 => '301 — Permanente', 302 => '302 — Temporaire', 307 => '307 — Temporaire stricte', 308 => '308 — Permanente stricte',
            ])->default(301)->required(),
            Toggle::make('is_active')->label('Active')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('updated_at', 'desc')->columns([
            TextColumn::make('source_path')->label('Source')->searchable()->weight('semibold'),
            TextColumn::make('target_url')->label('Destination')->limit(65),
            TextColumn::make('http_status')->label('Code')->badge(),
            IconColumn::make('is_active')->label('Active')->boolean(),
            TextColumn::make('hit_count')->label('Utilisations')->numeric()->sortable(),
            TextColumn::make('last_hit_at')->label('Dernière utilisation')->since()->placeholder('Jamais'),
        ])->filters([TernaryFilter::make('is_active')->label('Active')])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageRedirectRules::route('/')];
    }
}
