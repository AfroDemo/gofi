<?php

use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('payments/mobile-money/initiate', [PaymentController::class, 'initiate'])
        ->middleware('throttle:60,1');

    Route::get('payments/status/{reference}', [PaymentController::class, 'status'])
        ->middleware('throttle:120,1');

    Route::post('webhooks/palmpesa', [WebhookController::class, 'palmpesa'])
        ->middleware('throttle:300,1');

    Route::post('webhooks/snippe', [WebhookController::class, 'snippe'])
        ->middleware('throttle:300,1');
});
