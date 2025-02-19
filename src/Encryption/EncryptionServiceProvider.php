<?php

namespace Rose\Encryption;

use PhpOption\Option;
use PhpOption\Some;
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
        $this->app->singleton(Encryption::class, function ($app) {
            $key = $app->make('encryptionKey');
            $config = $app->make('config')->get('app');
            return new Encryption($key, $config['cipher']);
        });

        // Register encryptor alias
        $this->app->alias(Encryption::class, 'encryptor');
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
     * @return \Exception|Option
     */
    protected function key($config): \Exception|Option
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
