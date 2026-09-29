<?php

use App\Http\Controllers\Api\V1\MediaController;
use Illuminate\Support\Facades\Route;

/*
| Public, read-only media API shared by the Nuxt frontend and AI clients.
| Breaking changes go into a new version (e.g. /api/v2), never into v1.
*/
Route::prefix('v1')
    ->name('api.v1.')
    ->middleware('throttle:api')
    ->group(function () {
        Route::get('media', [MediaController::class, 'index'])->name('media.index');
        Route::get('media/{slug}', [MediaController::class, 'show'])->name('media.show');
    });
