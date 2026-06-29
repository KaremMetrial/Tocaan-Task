<?php

use Illuminate\Support\Facades\Route;
use Modules\Order\Http\Controllers\OrderController;

Route::middleware('auth:api')->group(function () {
    Route::apiResource('orders', OrderController::class);
});
