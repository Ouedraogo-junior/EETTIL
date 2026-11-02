<?php

use App\Modules\Paiements\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('payments/options', [PaymentController::class, 'options']);
    Route::get('payments/receivable', [PaymentController::class, 'receivable']);
    Route::get('payments', [PaymentController::class, 'index']);
    Route::post('payments/{payment}/cancel', [PaymentController::class, 'cancel']);

    Route::get('dossiers/{dossier}/payments', [PaymentController::class, 'forDossier']);
    Route::get('dossiers/{dossier}/invoices', [PaymentController::class, 'invoicesForDossier']);
    Route::post('documents/{document}/payments', [PaymentController::class, 'store']);
});