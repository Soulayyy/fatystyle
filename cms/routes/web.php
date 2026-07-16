<?php

use App\Http\Controllers\PagePreviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/preview/pages/{page}', PagePreviewController::class)
    ->middleware('auth')
    ->name('preview.pages.show');
