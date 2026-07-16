<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Contracts\View\View;

class PagePreviewController extends Controller
{
    public function __invoke(Page $page): View
    {
        abort_unless(auth()->user()?->can('pages.view'), 403);
        $page->load(['translations', 'blocks.translations']);

        return view('preview.page', [
            'page' => $page,
            'translation' => $page->translations->firstWhere('locale', config('cms.default_locale')),
        ]);
    }
}
