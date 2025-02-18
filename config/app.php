<?php

use Rose\Support\ServiceProvider;

return [

    /**
     * Application Name
     *
     */

    'name' => env('APP_NAME', 'ROSE'),

    'debug' => env('APP_DEBUG', false),

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    'key' => env('APP_KEY'),

    'cipher' => 'aes-256-gcm',

    /**
    *
    * Autoloaded ServiceProvidres
    *
    */

    'providers' => ServiceProvider::defaultProviders()->merge([
        // Insert package providers
    ])->merge([
        // Application service providers
    ]),

    /**
    *
    * Autoloaded ServiceProvidres
    *
    */

    'deferred' => ServiceProvider::defaultProviders()->merge([
        // Insert package providers
    ])->merge([
        // Application service providers
    ]),

    /**
    *
    * Autoloaded ServiceProvidres
    *
    */

    'eager' => ServiceProvider::defaultProviders()->merge([
        // Insert package providers
    ])->merge([
        // Application service providers
    ]),
];
