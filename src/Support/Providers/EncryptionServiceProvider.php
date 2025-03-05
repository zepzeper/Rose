<?php

namespace Rose\Support\Providers;

use PhpOption\Option;
use Rose\Encryption\Encryption;
use Rose\Exceptions\Encryption\MissingAppKeyException;
use Rose\Support\ServiceProvider;
use Rose\Support\Str;

class EncryptionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->bindEncryptionKey();
        $this->registerEncrypter();
    }

    /**
     * @return void
     */
    protected function bindEncryptionKey(): void
    {
        $this->app->bind('encryptionKey', function ($app) {
            $config = $app->make('config')->get('session');

            return $this->parseKey($config);
        });

    }

    /**
     * @return void
     */
    protected function registerEncrypter(): void
    {
        // Register the Encryption class itself
        $this->app->singleton('encryptor', function ($app) {
            $key = $app->make('encryptionKey');
            $config = $app->make('config')->get('app');
            return new Encryption($key, $config['cipher']);
        });

        // Register encryptor alias
        $this->app->alias('encryptor', Encryption::class);
    }

    /**
     *
     * @param  array $config
     * @return string
     */
    protected function parseKey($config): string
    {
        // If the key is an Option, get its value
        $key = $this->key($config);
        if ($key instanceof Option) {
            $key = $key->getOrElse(function() {
                throw new MissingAppKeyException;
            });
        }

        if (Str::startsWith($key, $prefix = 'base64:')) {
            $key = base64_decode(Str::remainder($key, $prefix));
        }

        return $key;
    }

    /**
     * @param  array $config
     * @return \Exception|string
     */
    protected function key($config): \Exception|string
    {
        return tap(
            $config['encryption_key'], function ($key) {
                if (empty($key)) {
                    throw new MissingAppKeyException;
                }
            }
        );
    }
}
