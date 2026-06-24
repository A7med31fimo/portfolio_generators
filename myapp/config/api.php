<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Versioning
    |--------------------------------------------------------------------------
    | Current API version prefix. Bump to 'v2' when introducing
    | breaking changes while keeping v1 routes available.
    */

    'version'       => env('API_VERSION', 'v1'),
    'current_prefix' => 'api/v1',

    /*
    |--------------------------------------------------------------------------
    | Reserved Usernames
    |--------------------------------------------------------------------------
    | These slugs cannot be claimed by any user. They conflict with
    | app routes, i18n locale prefixes, or brand identity.
    */

    'reserved_usernames' => [
        // API / infrastructure
        'api', 'webhook', 'health',
        // App routes
        'dashboard', 'login', 'register', 'logout',
        'forgot-password', 'reset-password',
        'profile', 'projects', 'themes', 'settings',
        'onboarding', 'publish',
        // Admin
        'admin', 'staff', 'moderator', 'mod', 'support',
        // i18n locale prefixes — must match i18n/routing.ts locales
        'en', 'ar', 'fr', 'de', 'es', 'pt', 'zh', 'ja', 'ko', 'ru',
        // Brand / marketing
        'nextdev', 'about', 'pricing', 'blog', 'docs',
        'help', 'contact', 'privacy', 'terms', 'security',
        'status', 'careers', 'press', 'changelog',
        // Common squatter targets
        'www', 'mail', 'app', 'root', 'system',
        'null', 'undefined', 'anonymous', 'official',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    'pagination' => [
        'default_per_page' => 15,
        'max_per_page'     => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    | Requests per minute per IP for auth endpoints.
    */

    'rate_limits' => [
        'login'           => 10,
        'register'        => 5,
        'forgot_password' => 3,
        'api'             => 60,
    ],

];
