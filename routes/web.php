<?php

use Illuminate\Support\Facades\Route;

// SPA Application - すべてのルートをVue Routerに委譲
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
