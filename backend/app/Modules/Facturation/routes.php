<?php

use App\Modules\Facturation\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('documents/options', [DocumentController::class, 'options']);
    Route::post('documents/preview', [DocumentController::class, 'preview']);
    Route::get('documents', [DocumentController::class, 'list']);

    Route::get('dossiers/{dossier}/documents', [DocumentController::class, 'index']);
    Route::post('dossiers/{dossier}/documents', [DocumentController::class, 'store']);
    Route::get('documents/{document}/pdf', [DocumentController::class, 'pdf']);

    Route::get('documents/{document}', [DocumentController::class, 'show']);
    Route::put('documents/{document}', [DocumentController::class, 'update']);
    Route::post('documents/{document}/convert', [DocumentController::class, 'convert']);
    Route::post('documents/{document}/cancel', [DocumentController::class, 'cancel']);
});