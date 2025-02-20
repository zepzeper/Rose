<?php

namespace Rose\Support\Providers;

use Rose\Contracts\Session\Storage as StorageContract;
use Rose\Encryption\Encryption;
use Rose\Support\ServiceProvider;
use Rose\Session\Manager\SessionManager;

class SessionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Make sure EncryptionServiceProvider is registered first
        if (!$this->app->bound(Encryption::class)) {
            $this->app->register(EncryptionServiceProvider::class);
        }

        $this->app->singleton('session.manager', function ($app) {
            return new SessionManager($app);
        });

        $this->app->singleton('session', function ($app) {
            return $app->make('session.manager')->driver();
        });

        $this->app->alias('session', StorageContract::class);
    }
}
