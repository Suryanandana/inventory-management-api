<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

// authentication
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('auth.login');
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum')->name('auth.logout');
Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->name('auth.verify-email');
Route::post('/resend-verification', [AuthController::class, 'resendVerification'])->middleware('throttle:5,1')->name('auth.resend-verification');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->middleware('throttle:5,1')->name('auth.forgot-password');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('auth.reset-password');

Route::get('/user', [AuthController::class, 'me'])->name('user')->middleware(['auth:sanctum', 'verified']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');

// Payment routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/payments/create-invoice', [PaymentController::class, 'createInvoice'])->name('payment.create-invoice');
    Route::get('/payments', [PaymentController::class, 'getPayments'])->name('payment.list');
    Route::get('/payments/{invoiceId}', [PaymentController::class, 'getInvoice'])->name('payment.detail');
    Route::post('/payments/{invoiceId}/expire', [PaymentController::class, 'expireInvoice'])->name('payment.expire');
});

// Webhook route (without authentication)
Route::post('/webhooks/xendit', [PaymentController::class, 'handleWebhook'])->name('webhook.xendit');

Route::post('/products', [ProductController::class, 'store']);