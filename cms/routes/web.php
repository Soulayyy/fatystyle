<?php

use App\Http\Controllers\BackupDownloadController;
use App\Http\Controllers\ContactExportController;
use App\Http\Controllers\ContentExportController;
use App\Http\Controllers\PagePreviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('filament.admin.auth.login');
});

Route::get('/preview/pages/{page}', PagePreviewController::class)
    ->middleware(['auth', 'can:pages.view'])
    ->name('preview.pages.show');

Route::get('/admin/exports/contacts.csv', ContactExportController::class)
    ->middleware(['auth', 'can:contacts.export'])
    ->name('admin.contacts.export');

Route::get('/admin/exports/content.json', ContentExportController::class)
    ->middleware(['auth', 'can:exports.export'])
    ->name('admin.content.export');

Route::get('/admin/backups/{backup}/download', BackupDownloadController::class)
    ->middleware(['auth', 'can:backups.export'])
    ->name('admin.backups.download');
