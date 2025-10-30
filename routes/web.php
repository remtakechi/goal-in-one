<?php

use App\Http\Controllers\SimpleRegistrationController;
use Illuminate\Support\Facades\Route;

// シンプル登録フォーム (非Vue)
Route::get('/simple-register', [SimpleRegistrationController::class, 'showRegistrationForm'])->name('simple-register');
Route::post('/simple-register', [SimpleRegistrationController::class, 'register'])->name('simple-register.store');

// SPA Application - すべてのルートをVue Routerに委譲
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
