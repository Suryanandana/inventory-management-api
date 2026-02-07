<?php
use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// authentication
Route::prefix('auth')->group(function(){
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('auth.login');
    Route::post('/logout', [AuthController::class, 'logout'])
        ->middleware('auth:sanctum')->name('auth.logout');
        Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
