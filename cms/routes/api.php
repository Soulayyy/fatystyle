<?php

use App\Http\Controllers\PublicContactController;
use Illuminate\Support\Facades\Route;

Route::post('/contact', PublicContactController::class)
    ->middleware('throttle:5,1')
    ->name('public.contact.store');
