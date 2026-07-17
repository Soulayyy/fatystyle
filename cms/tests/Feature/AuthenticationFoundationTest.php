<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\User;
use App\Providers\Filament\AdminPanelProvider;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolePermissionSeeder;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_users_cannot_access_the_admin_panel(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create(['is_active' => false]);
        $user->assignRole(RoleName::SuperAdministrator->value);

        $this->assertFalse($user->canAccessPanel(filament()->getPanel('admin')));
    }

    public function test_users_without_a_cms_role_cannot_access_the_admin_panel(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->assertFalse($user->canAccessPanel(filament()->getPanel('admin')));
    }

    public function test_super_administrator_receives_every_declared_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(RoleName::SuperAdministrator->value);

        $this->assertTrue($user->can('pages.publish'));
        $this->assertTrue($user->can('users.manage'));
        $this->assertTrue($user->can('backups.restore'));
    }

    public function test_editor_cannot_publish_or_manage_users(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(RoleName::Editor->value);

        $this->assertTrue($user->can('pages.update'));
        $this->assertFalse($user->can('pages.publish'));
        $this->assertFalse($user->can('users.manage'));
    }

    public function test_role_matrix_restricts_sensitive_operations(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $contentAdministrator = User::factory()->create();
        $contentAdministrator->assignRole(RoleName::ContentAdministrator->value);
        $this->assertTrue($contentAdministrator->can('pages.publish'));
        $this->assertFalse($contentAdministrator->can('users.view'));
        $this->assertFalse($contentAdministrator->can('backups.manage'));
        $this->assertFalse($contentAdministrator->can('backups.restore'));
        $this->assertFalse($contentAdministrator->can('backups.delete'));

        $validator = User::factory()->create();
        $validator->assignRole(RoleName::Validator->value);
        $this->assertTrue($validator->can('pages.publish'));
        $this->assertFalse($validator->can('users.view'));
        $this->assertFalse($validator->can('backups.view'));

        $auditor = User::factory()->create();
        $auditor->assignRole(RoleName::Auditor->value);
        $this->assertTrue($auditor->can('pages.view'));
        $this->assertTrue($auditor->can('contacts.export'));
        $this->assertFalse($auditor->can('pages.update'));
        $this->assertFalse($auditor->can('pages.publish'));
    }

    public function test_mfa_is_required_and_recovery_is_configured(): void
    {
        config()->set('cms.mfa_required', true);
        $panel = (new AdminPanelProvider($this->app))->panel(Panel::make());

        $this->assertTrue($panel->isMultiFactorAuthenticationRequired());
        $this->assertCount(1, $panel->getMultiFactorAuthenticationProviders());
    }

    public function test_bootstrap_seeder_never_resets_an_existing_account(): void
    {
        $repository = Env::getRepository();
        $keys = ['ADMIN_BOOTSTRAP_NAME', 'ADMIN_BOOTSTRAP_EMAIL', 'ADMIN_BOOTSTRAP_PASSWORD'];
        $previous = array_combine($keys, array_map($repository->get(...), $keys));
        $repository->set('ADMIN_BOOTSTRAP_NAME', 'Administratrice initiale');
        $repository->set('ADMIN_BOOTSTRAP_EMAIL', 'admin@example.test');
        $repository->set('ADMIN_BOOTSTRAP_PASSWORD', 'MotDePasseInitial-2026');

        try {
            $this->seed(DatabaseSeeder::class);
            $user = User::query()->where('email', 'admin@example.test')->firstOrFail();
            $this->assertTrue(Hash::check('MotDePasseInitial-2026', $user->password));
            $this->assertTrue($user->hasRole(RoleName::SuperAdministrator->value));

            $user->update(['password' => 'MotDePasseChoisi-2026']);
            $user->syncRoles([RoleName::Editor->value]);
            $repository->set('ADMIN_BOOTSTRAP_PASSWORD', 'AutreMotDePasse-2026');
            $this->seed(DatabaseSeeder::class);

            $user->refresh();
            $this->assertTrue(Hash::check('MotDePasseChoisi-2026', $user->password));
            $this->assertTrue($user->hasExactRoles([RoleName::Editor->value]));
        } finally {
            foreach ($previous as $key => $value) {
                $value === null ? $repository->clear($key) : $repository->set($key, $value);
            }
        }
    }
}
