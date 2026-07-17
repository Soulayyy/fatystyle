<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupDownloadController extends Controller
{
    public function __invoke(Backup $backup): BinaryFileResponse
    {
        abort_unless(auth()->user()?->can('backups.view'), 403);
        abort_unless($backup->status === 'completed' && $backup->path && is_file($backup->path), 404);

        return response()->download($backup->path);
    }
}
