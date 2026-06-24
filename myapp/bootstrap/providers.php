<?php

/**
 * Laravel 12 registers application service providers explicitly here
 * instead of in config/app.php's 'providers' array.
 *
 * Without this file, AppServiceProvider::boot() never runs —
 * which means named rate limiters (RateLimiter::for(...)) and
 * repository interface bindings are never registered, causing:
 *   "Rate limiter [auth_register] is not defined."
 */

return [
    App\Providers\AppServiceProvider::class,
];
