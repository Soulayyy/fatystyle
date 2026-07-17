<?php

namespace App\Http\Controllers;

use App\Services\Publishing\PublicContentBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContentExportController extends Controller
{
    public function __invoke(PublicContentBuilder $builder): StreamedResponse
    {
        abort_unless(auth()->user()?->can('exports.export'), 403);
        $json = json_encode($builder->build(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;

        return response()->streamDownload(fn () => print $json, 'contenu-fatystyle-'.now()->format('Ymd-His').'.json', [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    }
}
