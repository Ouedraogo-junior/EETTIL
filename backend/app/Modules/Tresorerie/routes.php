<?php

use App\Modules\Tresorerie\Http\Controllers\AccountController;
use App\Modules\Tresorerie\Http\Controllers\MovementController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('accounts', [AccountController::class, 'index']);

    Route::get('movements/options', [MovementController::class, 'options']);
    Route::get('movements', [MovementController::class, 'index']);
    Route::post('movements', [MovementController::class, 'store']);
    Route::post('movements/{movement}/cancel', [MovementController::class, 'cancel']);
});