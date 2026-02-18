<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
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
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show')->middleware(['auth:sanctum', 'verified']);
Route::post('/products', [ProductController::class, 'store']);