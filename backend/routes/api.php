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

Route::get('health', HealthCheckController::class);

Route::post('/users/register', [UserController::class, 'store']);
Route::get('/verify-email/{id}/{hash}', [UserController::class, 'verifyEmail'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');
Route::post('/login', [AuthenticatedSessionController::class, 'store']);

Route::middleware('auth:sanctum')->group(function() {
    Route::get('/users/me', [UserController::class, 'me']);

    Route::resource('my/items', MyItemController::class);
});

Route::resource('items', ItemController::class, ['only' => ['index', 'show']]);

// Notes: 以下参考用に Laravel Breeze が自動生成した Route
//Route::post('/users/register', [UserController::class, 'store'])
//    ->middleware('guest')
//    ->name('register');
//
//Route::post('/login', [AuthenticatedSessionController::class, 'store'])
//    ->middleware('guest')
//    ->name('login');
//Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
//                ->middleware('guest')
//                ->name('password.email');
//
//Route::post('/reset-password', [NewPasswordController::class, 'store'])
//                ->middleware('guest')
//                ->name('password.store');
//Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
//    ->middleware(['auth', 'throttle:6,1'])
//    ->name('verification.send');
