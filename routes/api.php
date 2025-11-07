<?php

use Illuminate\Support\Facades\Route;
use ESolution\GoSend\Http\Controllers\WebhookController;

Route::group([
    'prefix' => config('gosend.routes.prefix', 'gosend'),
    'middleware' => config('gosend.routes.middleware', ['api']),
], function () {
    Route::post('/webhook', [WebhookController::class, 'handle'])
        ->name(config('gosend.webhook.route_name', 'gosend.webhook'));
});
