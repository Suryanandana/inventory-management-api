<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

// authentication
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('auth.login');
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum')->name('auth.logout');
Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
Route::post('/verify-email', [AuthController::class, 'verifyEmail'])->name('auth.verify-email');
Route::get('/user', [AuthController::class, 'me'])->name('user')->middleware('auth:sanctum');
