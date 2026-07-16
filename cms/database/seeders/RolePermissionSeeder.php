<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /** @var list<string> */
    private const MODULES = [
        'dashboard',
        'pages',
        'navigation',
        'site-settings',
        'services',
        'creation-categories',
        'media',
        'contacts',
        'seo',
        'redirects',
        'releases',
        'backups',
        'users',
        'audit-log',
        'exports',
    ];

    /** @var list<string> */
    private const ACTIONS = [
        'view',
        'create',
        'update',
        'delete',
        'restore',
        'publish',
        'export',
        'manage',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(self::MODULES)
            ->crossJoin(self::ACTIONS)
            ->map(fn (array $parts): string => implode('.', $parts));

        $permissions->each(fn (string $permission) => Permission::findOrCreate($permission));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $superAdmin = Role::findOrCreate(RoleName::SuperAdministrator->value);
        $superAdmin->syncPermissions($permissions);

        Role::findOrCreate(RoleName::ContentAdministrator->value)->syncPermissions(
            $permissions->reject(fn (string $permission): bool => str_starts_with($permission, 'users.')
                || $permission === 'backups.restore'
                || $permission === 'backups.manage'),
        );

        Role::findOrCreate(RoleName::Editor->value)->syncPermissions(
            $permissions->filter(fn (string $permission): bool => in_array(
                explode('.', $permission)[1],
                ['view', 'create', 'update'],
                true,
            ))->reject(fn (string $permission): bool => str_starts_with($permission, 'users.')
                || str_starts_with($permission, 'backups.')
                || str_starts_with($permission, 'audit-log.')),
        );

        Role::findOrCreate(RoleName::Validator->value)->syncPermissions(
            $permissions->filter(fn (string $permission): bool => in_array(
                explode('.', $permission)[1],
                ['view', 'update', 'publish'],
                true,
            ))->reject(fn (string $permission): bool => str_starts_with($permission, 'users.')
                || str_starts_with($permission, 'backups.')),
        );

        Role::findOrCreate(RoleName::Auditor->value)->syncPermissions(
            $permissions->filter(fn (string $permission): bool => in_array(
                explode('.', $permission)[1],
                ['view', 'export'],
                true,
            )),
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
