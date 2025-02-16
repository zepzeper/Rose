<?php

namespace Rose\Session\Generator;

use Rose\Session\Storage\NativeSessionStorage;
use Rose\Security\Encryption;
use InvalidArgumentException;
use Rose\Contracts\Session\Storage\Storage;

class SessionFactory
{
    public function __construct(
        protected array $config,
        protected Encryption $encryption
    ) {}

    public function driver(string $driver = null): Storage
    {
        $driver = $driver ?? $this->config['driver'];
        $config = $this->config;

        $storage = match($driver) {
            'native' => new NativeSessionStorage($config),
            //'database' => new DatabaseSessionStorage(
            //    $config,
            //    app('db')->connection($config['connection'] ?? null)
            //),
            default => throw new InvalidArgumentException("Unsupported session driver: $driver")
        };

        return $storage;
    }
}
