<?php

namespace App\Providers;

use App\Repositories\Contracts\ProfileRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Eloquent\EloquentProfileRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register services and bind interfaces to implementations.
     * This is the IoC container — swap EloquentUserRepository for
     * a different implementation here without touching any controller.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, EloquentUserRepository::class);
        $this->app->bind(ProfileRepositoryInterface::class, EloquentProfileRepository::class);
    }

    /**
     * Bootstrap application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Configure named rate limiters used in routes/api.php.
     */
    private function configureRateLimiting(): void
    {
        // Login: 10 attempts per minute per IP
        RateLimiter::for('auth_login', function (Request $request) {
            return Limit::perMinute(config('api.rate_limits.login', 10))
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Too many login attempts. Please try again in a minute.',
                        'errors'  => [],
                    ], 429);
                });
        });

        // Register: 5 attempts per minute per IP
        RateLimiter::for('auth_register', function (Request $request) {
            return Limit::perMinute(config('api.rate_limits.register', 5))
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Too many registration attempts. Please slow down.',
                        'errors'  => [],
                    ], 429);
                });
        });

        // Forgot password: 3 attempts per minute per IP
        RateLimiter::for('auth_forgot', function (Request $request) {
            return Limit::perMinute(config('api.rate_limits.forgot_password', 3))
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Too many password reset attempts. Please wait a minute.',
                        'errors'  => [],
                    ], 429);
                });
        });

        // General API: 60 requests per minute per user or IP
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(config('api.rate_limits.api', 60))
                ->by($request->user()?->id ?: $request->ip());
        });
    }
}
