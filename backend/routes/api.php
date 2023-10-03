<?php

use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\MyItemController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
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

Route::get('health', HealthCheckController::class);

Route::post('login', [UserController::class, 'login']);

Route::middleware('auth:sanctum')->group(function() {
    Route::get('/users/me', [UserController::class, 'me']);

    Route::resource('my/items', MyItemController::class);
});

Route::resource('items', ItemController::class, ['only' => ['index', 'show']]);
