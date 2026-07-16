<?php

namespace App\Filament\Resources\Concerns;

use Illuminate\Database\Eloquent\Model;

trait AuthorizesCmsResource
{
    public static function canViewAny(): bool
    {
        return auth()->user()?->can(static::PERMISSION_MODULE.'.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can(static::PERMISSION_MODULE.'.create') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can(static::PERMISSION_MODULE.'.update') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can(static::PERMISSION_MODULE.'.delete') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can(static::PERMISSION_MODULE.'.delete') ?? false;
    }

    public static function canRestore(Model $record): bool
    {
        return auth()->user()?->can(static::PERMISSION_MODULE.'.restore') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return auth()->user()?->can(static::PERMISSION_MODULE.'.restore') ?? false;
    }

    public static function canForceDelete(Model $record): bool
    {
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        return false;
    }
}
