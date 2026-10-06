<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Billing\ShopController;

/*
|--------------------------------------------------------------------------
| Billing / Shop Routes (User)
|--------------------------------------------------------------------------
|
| Prefix: /billing  (via RouteServiceProvider)
| Middleware: auth.session, RequireTwoFactorAuthentication
|
*/

Route::get('/', [ShopController::class, 'index'])->name('billing.shop');
Route::get('/orders', [ShopController::class, 'orders'])->name('billing.orders');
Route::post('/order', [ShopController::class, 'createOrder'])->name('billing.order.create');
