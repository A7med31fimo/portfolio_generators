<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Version 1
|--------------------------------------------------------------------------
| All routes are prefixed /api/v1 by this file.
| Future breaking changes go in routes/api_v2.php with a new controller
| namespace, keeping v1 untouched for backwards compatibility.
*/

Route::prefix('api/v1')->group(function () {

    // ── Health check (unauthenticated) ────────────────────────────────────
    Route::get('/health', function () {
        return response()->json([
            'status'  => true,
            'message' => 'NextDev API is running',
            'data'    => [
                'version'     => config('api.version'),
                'environment' => app()->environment(),
                'timestamp'   => now()->toISOString(),
            ],
        ]);
    });

    // ── Auth routes ───────────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {

        // Public auth endpoints — rate-limited
        Route::middleware('throttle:auth_register')->group(function () {
            Route::post('/register', [AuthController::class, 'register']);
        });

        Route::middleware('throttle:auth_login')->group(function () {
            Route::post('/login', [AuthController::class, 'login']);
        });

        Route::middleware('throttle:auth_forgot')->group(function () {
            Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
            Route::post('/reset-password',  [AuthController::class, 'resetPassword']);
        });

        // Email verification.
        //
        // The GET route below MUST be named 'verification.verify' — Laravel's
        // built-in VerifyEmail notification (fired automatically by the
        // Registered event on every new user, since User implements
        // MustVerifyEmail) hardcodes a call to route('verification.verify', ...)
        // when building the signed verification link for the email. Without
        // this named route, registration itself throws RouteNotFoundException
        // before the HTTP response is ever returned.
        Route::middleware(['auth:sanctum', 'signed', 'throttle:6,1'])
            ->get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
            ->name('verification.verify');

        Route::middleware(['auth:sanctum', 'throttle:6,1'])
            ->post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])
            ->name('verification.send');

        // Protected auth endpoints — require Sanctum token
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me',     [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });

    });

    // ── Username availability (public) ────────────────────────────────────
    Route::get('/auth/check-username', [AuthController::class, 'checkUsername']);

});
