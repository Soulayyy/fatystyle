<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Models\CreationCategory;
use App\Models\Service;
use App\Services\Content\CatalogWorkflow;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_service_follows_the_validated_editorial_workflow(): void
    {
        $service = Service::create([
            'slug' => 'patronage', 'title' => 'Patronage',
            'description' => 'Création de patrons adaptés aux besoins techniques de chaque projet textile.',
            'status' => ContentStatus::Draft,
        ]);
        $workflow = app(CatalogWorkflow::class);
        $workflow->transition($service, ContentStatus::InReview);
        $workflow->transition($service->refresh(), ContentStatus::Approved);
        $workflow->transition($service->refresh(), ContentStatus::Published);

        $this->assertSame(ContentStatus::Published, $service->refresh()->status);
        $this->assertTrue($service->is_visible);
        $this->assertNotNull($service->published_at);
    }

    public function test_scheduling_requires_a_future_date(): void
    {
        $category = CreationCategory::create([
            'slug' => 'sur-mesure', 'title' => 'Sur mesure',
            'description' => 'Des vêtements imaginés et confectionnés selon vos envies et votre silhouette.',
            'status' => ContentStatus::Approved,
            'scheduled_at' => now()->subMinute(),
        ]);

        $this->expectException(DomainException::class);
        app(CatalogWorkflow::class)->transition($category, ContentStatus::Scheduled);
    }
}
