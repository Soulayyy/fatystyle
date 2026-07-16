<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\PageTemplate;
use App\Models\Page;
use App\Services\Content\PageWorkflow;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_editorial_workflow_captures_an_immutable_version_for_each_transition(): void
    {
        $page = Page::create([
            'template' => PageTemplate::Editorial,
            'status' => ContentStatus::Draft,
        ]);

        $workflow = app(PageWorkflow::class);
        $workflow->transition($page, ContentStatus::InReview);
        $workflow->transition($page->refresh(), ContentStatus::Approved);
        $workflow->transition($page->refresh(), ContentStatus::Published);

        $this->assertSame(ContentStatus::Published, $page->refresh()->status);
        $this->assertNotNull($page->published_at);
        $this->assertSame(3, $page->versions()->count());
        $this->assertSame([1, 2, 3], $page->versions()->reorder('version')->pluck('version')->all());
    }

    public function test_invalid_transition_is_rejected_server_side(): void
    {
        $page = Page::create([
            'template' => PageTemplate::Editorial,
            'status' => ContentStatus::Draft,
        ]);

        $this->expectException(DomainException::class);
        app(PageWorkflow::class)->transition($page, ContentStatus::Published);
    }

    public function test_schedule_requires_a_future_date(): void
    {
        $page = Page::create([
            'template' => PageTemplate::Editorial,
            'status' => ContentStatus::Approved,
            'scheduled_at' => now()->subMinute(),
        ]);

        $this->expectException(DomainException::class);
        app(PageWorkflow::class)->transition($page, ContentStatus::Scheduled);
    }
}
