<?php

/**
 * CORS — Cross-Origin Resource Sharing
 *
 * CORS_ORIGIN di .env menentukan asal Next.js frontend (http://localhost:3000).
 */
return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(array_map(
        'trim',
        explode(',', env('CORS_ORIGIN', 'http://localhost:3000'))
    )),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
