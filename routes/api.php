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
    // Members: login with mobile number + OTP
    Route::post('auth/otp', [V1\AuthController::class, 'sendLoginOtp'])->middleware('throttle:5,10')->name('auth.otp.send');
    Route::post('auth/otp/verify', [V1\AuthController::class, 'verifyLoginOtp'])->middleware('throttle:10,10')->name('auth.otp.verify');
    // Self-registration: pick a gym, verify mobile, send a join request for the gym admin to approve
    Route::get('gyms', [V1\RegistrationController::class, 'gyms'])->name('gyms');
    Route::post('register/otp', [V1\RegistrationController::class, 'sendOtp'])->middleware('throttle:5,10')->name('register.otp');
    Route::post('register', [V1\RegistrationController::class, 'register'])->middleware('throttle:10,10')->name('register');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('registration', [V1\RegistrationController::class, 'status'])->name('registration');
        Route::post('registration', [V1\RegistrationController::class, 'apply'])->middleware('throttle:10,10')->name('registration.apply');
        Route::get('auth/me', [V1\AuthController::class, 'me'])->name('auth.me');
        Route::get('branding', [V1\AuthController::class, 'branding'])->name('branding');
        Route::post('auth/logout', [V1\AuthController::class, 'logout'])->name('auth.logout');
    });

    // Member registration through a gym's QR join token
    Route::get('join/{token}', [V1\JoinController::class, 'show'])->name('join.show');
    Route::post('join/{token}/otp', [V1\JoinController::class, 'sendOtp'])->middleware('throttle:5,10')->name('join.otp.send');
    Route::post('join/{token}/otp/verify', [V1\JoinController::class, 'verifyOtp'])->middleware('throttle:10,10')->name('join.otp.verify');
    Route::post('join/{token}', [V1\JoinController::class, 'store'])->middleware('throttle:10,10')->name('join.store');
});
