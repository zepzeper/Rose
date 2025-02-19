<?php

use Rose\Support\Env;

return [

    /**
    * Session driver
    *
    * Currently supported native only...
    */
    'driver' => 'native',

    'encryption_key' => Env::get('SESSION_ENCRYPTION_KEY'),

    'cipher' => Env::get('SESSION_CIPHER', 'aes-256-gcm'),

    /**
     * Session lifetime
     *
     * Expire the session when the application is closed.
     * Explicitly tell the time a session exists.
     */
    'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),
    'lifetime' => env('SESSION_LIFE_TIME', 120),

    /**
    * Session encryption
    *
    * This allows you to encrypt all session related data.
    */

    'encrypt' => env('SESSION_ENCRYPT', true),

    /**
    * Session required values
    */
    'cookie' => env('SESSION_COOKIE'),
    'path' => env('SESSION_PATH', '/'),
    'domain' => env('SESSION_DOMAIN'),
    'secure' => env('SESSION_SECURE'),
    'http_only' => env('SESSION_HTTP_ONLY', true),
    'same_site' => env('SESSION_SAME_SITE', 'lax'),
    
    'lottery' => [2, 100], // garbage collection lottery
];
