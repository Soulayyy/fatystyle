<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
