<?php

use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\MyItemController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::middleware(['json_unescape_unicode'])->group(function(){
    Route::get('health', HealthCheckController::class);

    Route::post('/users/register', [UserController::class, 'store']);
    Route::get('/verify-email/{id}/{hash}', [UserController::class, 'verifyEmail'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/login', [UserController::class, 'login']);

    Route::middleware(['auth:sanctum', 'verified'])->group(function() {
        Route::get('/users/me', [UserController::class, 'me']);

        Route::resource('my/items', MyItemController::class);

        Route::post('items/{id}/buy', [ItemController::class, 'buyItem']);
    });

    Route::resource('items', ItemController::class, ['only' => ['index', 'show']]);

});

