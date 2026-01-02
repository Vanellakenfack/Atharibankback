<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure CORS settings for your application. This file
    | controls CORS behavior for routes defined in your application.
    |
    */

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'login',
        'logout',
        'frais-commissions',
        'frais-applications',
        'types-comptes',
        'comptes/*'
    ],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*', 'Authorization', 'Content-Type', 'X-Requested-With', 'X-CSRF-TOKEN'],
    'exposed_headers' => ['*'],
    'max_age' => 0,
    'supports_credentials' => true,



];
