<?php

use Illuminate\Support\Facades\Route;

// This app serves the API only; the human-facing UI is the Nuxt frontend.
Route::get('/', fn () => response()->json([
    'name' => config('app.name'),
    'api' => url('/api/v1/media'),
]));
