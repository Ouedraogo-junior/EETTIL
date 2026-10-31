<?php

use App\Modules\Dossiers\Http\Controllers\DossierController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('dossiers')->group(function () {
    Route::get('options', [DossierController::class, 'options']);
    Route::get('/', [DossierController::class, 'index']);
    Route::post('/', [DossierController::class, 'store']);
    Route::get('{dossier}', [DossierController::class, 'show']);
    Route::put('{dossier}', [DossierController::class, 'update']);
    Route::patch('{dossier}/status', [DossierController::class, 'changeStatus']);
    Route::delete('{dossier}', [DossierController::class, 'destroy'])->middleware('role:admin');
});