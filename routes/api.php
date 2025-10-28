<?php 

use App\Http\Controllers\Api\PaymentWebhookController;
use Illuminate\Support\Facades\Route;



Route::post('/payment/webhook', [PaymentWebhookController::class, 'handle'])
    ->withoutMiddleware(['auth:sanctum', 'auth:api', 'web'])
    ->name('payment.webhook');

Route::get('/payment/recieved', [PaymentWebhookController::class, 'recieved']);

Route::post('/payment/check', [PaymentWebhookController::class,'checkStatus']);
