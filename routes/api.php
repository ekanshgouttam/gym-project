<?php

use App\Http\Controllers\Api\V1;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/* ---------- Mobile app API, version 1 ---------- */
Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::get('app-version', [V1\AppVersionController::class, 'show'])->name('app-version');

    Route::post('auth/login', [V1\AuthController::class, 'login'])->middleware('throttle:10,1')->name('auth.login');
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [V1\AuthController::class, 'me'])->name('auth.me');
        Route::post('auth/logout', [V1\AuthController::class, 'logout'])->name('auth.logout');
    });

    // Member registration through a gym's QR join token
    Route::get('join/{token}', [V1\JoinController::class, 'show'])->name('join.show');
    Route::post('join/{token}/otp', [V1\JoinController::class, 'sendOtp'])->middleware('throttle:5,10')->name('join.otp.send');
    Route::post('join/{token}/otp/verify', [V1\JoinController::class, 'verifyOtp'])->middleware('throttle:10,10')->name('join.otp.verify');
    Route::post('join/{token}', [V1\JoinController::class, 'store'])->middleware('throttle:10,10')->name('join.store');
});
