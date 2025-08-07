<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GoalController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::get('/auth/user', [AuthController::class, 'user']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::delete('/auth/account', [AuthController::class, 'deleteAccount']);
    
    // Dashboard routes
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/goals/{goalUuid}/progress', [DashboardController::class, 'goalProgress']);
    
    // Goals routes
    Route::apiResource('goals', GoalController::class)->parameters([
        'goals' => 'uuid'
    ]);
    
    // Tasks routes
    Route::get('/tasks', [TaskController::class, 'indexAll']); // Get all tasks for user
    Route::post('/tasks', [TaskController::class, 'storeIndependent']); // Create task independently
    Route::get('/goals/{goalUuid}/tasks', [TaskController::class, 'index']);
    Route::post('/goals/{goalUuid}/tasks', [TaskController::class, 'store']);
    Route::get('/tasks/{uuid}', [TaskController::class, 'show']);
    Route::put('/tasks/{uuid}', [TaskController::class, 'update']);
    Route::delete('/tasks/{uuid}', [TaskController::class, 'destroy']);
    Route::post('/tasks/{uuid}/complete', [TaskController::class, 'complete']);
});