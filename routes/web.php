<?php

use Illuminate\Support\Facades\Route;
// in routes/web.php
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\Api\PaymentWebhookController;


// Route::get('/orders/{order}/pay', [PaymentController::class, 'showQR'])->name('orders.pay');
// Route::get('/orders/{order}/check-status', [PaymentController::class, 'checkStatus'])->name('orders.check');


Route::get('/khqr/pay', [PaymentController::class, 'manualPay'])->name('khqr.pay');
Route::post('/khqr/check', [PaymentController::class, 'checkStatus'])->name('khqr.check');
