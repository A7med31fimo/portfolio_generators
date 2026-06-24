<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS)
    |--------------------------------------------------------------------------
    | Controls which origins, headers, and methods the API accepts.
    | The 'allowed_origins' should match your Next.js frontend domains.
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:3000'),
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'X-Requested-With',
        'Authorization',
        'Accept',
        'X-CSRF-TOKEN',
        'X-Locale',
    ],

    'exposed_headers' => [],

    'max_age' => 0,

    /*
    | allow_credentials MUST be true for Sanctum cookie-based SPA auth.
    | Keep true even when using token-based auth — it doesn't hurt.
    */
    'supports_credentials' => true,

];
