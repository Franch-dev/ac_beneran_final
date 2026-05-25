<?php

use App\Http\Controllers\BackendOpsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:admin', 'throttle:writes'])->prefix('backend')->group(function (): void {
    Route::get('/health/db', [BackendOpsController::class, 'dbHealth']);
    Route::get('/skills', [BackendOpsController::class, 'listSkills']);
    Route::get('/skills/relevant', [BackendOpsController::class, 'relevantSkills']);
    Route::post('/skills/sync', [BackendOpsController::class, 'syncSkills']);
});
