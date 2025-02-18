<?php

namespace Rose\Encryption;

use Rose\Exceptions\Encryption\MissingAppKeyException;
use Rose\Support\ServiceProvider;
use Rose\Support\Str;

class EncryptionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerEncrypter();
    }

    /**
     * @return void
     */
    protected function registerEncrypter(): void
    {
        $this->app->singleton('encrypter', function ($app){
            $config = $app->make('config')->get('app');

            $key = $this->parseKey($config);

            return (new Encryption($key, $config['cipher']));
        });
    }

    /**
    *
    * @param array $config
    * @return string
    */
    protected function parseKey($config): string
    {
        if (Str::startsWith($key = $this->key($config), $prefix = 'base64:')) {
            $key = base64_decode(Str::remainder($key, $prefix));
        }

        return $key;
    }

    /**
     * @param mixed $config
     */
    protected function key($config)
    {
        return tap($config['key'], function ($key) {
            if (empty($key)) {
                throw new MissingAppKeyException;
            }
        });
    }
}
