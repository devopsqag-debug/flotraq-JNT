<?php

use Illuminate\Support\Facades\Route;
use JntExpress\Http\Controllers\RateController;
use JntExpress\Http\Controllers\SettingsController;
use JntExpress\Http\Controllers\ShipmentController;
use JntExpress\Http\Controllers\WebhookController;
use JntExpress\Http\Middleware\VerifyJntWebhook;

$name = config('jnt.routes.name', 'jnt.');

// J&T -> your app. No session / CSRF.
Route::post(config('jnt.webhook.path', 'jnt-webhook'), WebhookController::class)
    ->middleware(array_merge(config('jnt.webhook.middleware', []), [VerifyJntWebhook::class]))
    ->name($name . 'webhook');

Route::prefix(config('jnt.routes.prefix', 'jnt'))->group(function () use ($name) {

    // Public checkout quote
    Route::post('rates', RateController::class)
        ->middleware(config('jnt.routes.rate_middleware', []))
        ->name($name . 'rates');

    // Admin
    Route::middleware(config('jnt.routes.middleware', ['web', 'auth']))->group(function () use ($name) {
        Route::get('settings', [SettingsController::class, 'edit'])->name($name . 'settings.edit');
        Route::put('settings', [SettingsController::class, 'update'])->name($name . 'settings.update');

        Route::get('shipments', [ShipmentController::class, 'index'])->name($name . 'shipments.index');
        Route::post('shipments', [ShipmentController::class, 'store'])->name($name . 'shipments.store');
        Route::post('shipments/cancel', [ShipmentController::class, 'cancel'])->name($name . 'shipments.cancel');
        Route::match(['get', 'post'], 'shipments/print', [ShipmentController::class, 'printBulk'])->name($name . 'shipments.print.bulk');
        Route::get('shipments/{shipment}', [ShipmentController::class, 'show'])->name($name . 'shipments.show');
        Route::get('shipments/{shipment}/print/{format}', [ShipmentController::class, 'printOne'])->name($name . 'shipments.print');
    });
});
