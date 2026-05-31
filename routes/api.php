<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DocumentSubmissionController;
use App\Http\Controllers\Api\DocumentTypeController;
use App\Http\Controllers\Api\PeriodController;
use App\Http\Controllers\Api\ProgramController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Authentication Route
Route::post('/login', [AuthController::class, 'login']);

// Protected routes (Requires Sanctum Token)
Route::middleware('auth:sanctum')->group(function () {

    // Auth profile endpoints
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Academic Programs endpoint (for select options, accessible to authenticated users)
    Route::get('/programs', [ProgramController::class, 'index']);

    // User management endpoints with explicit permission protection
    Route::middleware('permission:users.view')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{user}', [UserController::class, 'show']);
    });

    Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.create');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('permission:users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete');

    // Role and permission management endpoints (Protected by roles.manage)
    Route::middleware('permission:roles.manage')->group(function () {
        Route::get('/roles', [RoleController::class, 'index']);
        Route::get('/roles/{role}', [RoleController::class, 'show']);
        Route::post('/roles/{role}/permissions', [RoleController::class, 'syncPermissions']);
    });

    // ─── Document Review System ───────────────────────────────────────────────

    // Admin-only: document types and periods management
    Route::middleware('role:Administrador')->group(function () {
        Route::apiResource('document-types', DocumentTypeController::class);
        Route::apiResource('periods', PeriodController::class);
        Route::patch('periods/{period}/activate', [PeriodController::class, 'activate']);
    });

    // Admin + coordinator: subjects and dashboard
    Route::middleware('role:Administrador,Coordinador')->group(function () {
        Route::apiResource('subjects', SubjectController::class);
        Route::get('dashboard', [DashboardController::class, 'index']);
    });

    // Admin + coordinator: document submissions
    Route::middleware('role:Administrador,Coordinador')->group(function () {
        Route::get('submissions', [DocumentSubmissionController::class, 'index']);
        Route::post('submissions', [DocumentSubmissionController::class, 'store']);
        Route::get('submissions/{uuid}', [DocumentSubmissionController::class, 'show']);
        Route::post('submissions/{uuid}/confirm', [DocumentSubmissionController::class, 'confirm']);
        Route::delete('submissions/{uuid}', [DocumentSubmissionController::class, 'destroy']);
    });
});
