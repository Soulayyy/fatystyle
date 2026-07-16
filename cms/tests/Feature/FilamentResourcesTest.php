<?php

namespace Tests\Feature;

use App\Enums\RoleName;
use App\Filament\Resources\CreationCategories\CreationCategoryResource;
use App\Filament\Resources\MediaAssets\MediaAssetResource;
use App\Filament\Resources\NavigationItems\NavigationItemResource;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Services\ServiceResource;
use App\Filament\Resources\SiteSettings\SiteSettingResource;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentResourcesTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_administrator_can_open_every_content_resource(): void
    {
        config()->set('cms.mfa_required', false);
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole(RoleName::SuperAdministrator->value);

        $this->actingAs($user);

        $this->get(PageResource::getUrl())->assertOk();
        $this->get(PageResource::getUrl('create'))->assertOk();
        $this->get(MediaAssetResource::getUrl())->assertOk();
        $this->get(MediaAssetResource::getUrl('create'))->assertOk();
        $this->get(NavigationItemResource::getUrl())->assertOk();
        $this->get(ServiceResource::getUrl())->assertOk();
        $this->get(CreationCategoryResource::getUrl())->assertOk();
        $this->get(SiteSettingResource::getUrl())->assertOk();
    }
}
