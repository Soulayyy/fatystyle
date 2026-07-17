<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Models\Backup;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProtectedDownloadsTest extends TestCase
{
    use RefreshDatabase;

    public function test_cms_root_redirects_to_the_administration_login(): void
    {
        $this->get('/')->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_editor_cannot_bypass_export_permissions_with_direct_urls(): void
    {
        config()->set('cms.mfa_required', false);
        $this->seed(RolePermissionSeeder::class);
        $editor = User::factory()->create();
        $editor->assignRole(RoleName::Editor->value);

        $this->actingAs($editor);
        $this->get(route('admin.contacts.export'))->assertForbidden();
        $this->get(route('admin.content.export'))->assertForbidden();
    }

    public function test_backup_download_requires_the_dedicated_export_permission(): void
    {
        config()->set('cms.mfa_required', false);
        $this->seed(RolePermissionSeeder::class);
        $backup = Backup::query()->create([
            'type' => 'database',
            'status' => 'completed',
            'path' => sys_get_temp_dir().'/backup-that-must-not-be-read.dump',
        ]);
        $editor = User::factory()->create();
        $editor->assignRole(RoleName::Editor->value);

        $this->actingAs($editor)
            ->get(route('admin.backups.download', $backup))
            ->assertForbidden();
    }
}
