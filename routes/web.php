<?php

use Illuminate\Support\Facades\Route;

// SPA — Vue Router handles all frontend routes
Route::get('/acp-sales-guide/{any?}', function () {
    return view('acpsalesguide::index');
})->where('any', '.*');
