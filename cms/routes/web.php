<?php

use App\Http\Controllers\BackupDownloadController;
use App\Http\Controllers\ContactExportController;
use App\Http\Controllers\ContentExportController;
use App\Http\Controllers\PagePreviewController;
use App\Http\Controllers\PublicContactController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/preview/pages/{page}', PagePreviewController::class)
    ->middleware('auth')
    ->name('preview.pages.show');

Route::post('/api/contact', PublicContactController::class)
    ->middleware('throttle:5,1')
    ->name('public.contact.store');

Route::get('/admin/exports/contacts.csv', ContactExportController::class)
    ->middleware('auth')
    ->name('admin.contacts.export');

Route::get('/admin/exports/content.json', ContentExportController::class)
    ->middleware('auth')->name('admin.content.export');

Route::get('/admin/backups/{backup}/download', BackupDownloadController::class)
    ->middleware('auth')->name('admin.backups.download');
