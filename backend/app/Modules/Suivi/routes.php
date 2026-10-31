<?php

use App\Modules\Suivi\Http\Controllers\SuiviController;
use App\Modules\Suivi\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('suivi', [SuiviController::class, 'index']);
    Route::post('dossiers/{dossier}/tracking', [TrackingController::class, 'store']);
    Route::put('dossiers/{dossier}/tracking', [TrackingController::class, 'update']);
    Route::get('suivi/export', [SuiviController::class, 'export']);
});