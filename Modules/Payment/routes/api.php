<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\PaymentController;
use Modules\Payment\Http\Controllers\WebhookController;

Route::middleware('auth:api')->group(function () {
    Route::get('payments', [PaymentController::class, 'index']);
    Route::get('orders/{order}/payments', [PaymentController::class, 'forOrder']);
    Route::post('orders/{order}/payments', [PaymentController::class, 'store']);
});

// Gateway callbacks — unauthenticated; verified per-gateway via signature.
Route::post('payments/webhook/{gateway}', [WebhookController::class, 'handle']);
