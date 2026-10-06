<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Billing\MidtransWebhookController;

/*
|--------------------------------------------------------------------------
| Billing Webhook Routes
|--------------------------------------------------------------------------
|
| No auth, no CSRF — verified internally by Midtrans signature.
|
*/

Route::post('/webhook/midtrans', MidtransWebhookController::class)->name('billing.webhook.midtrans');
