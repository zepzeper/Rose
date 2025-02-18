<?php

namespace Rose\Session;

use Rose\Contracts\Session\Kernel as SessionContract;
use Rose\Contracts\Session\Storage\Storage;
use Rose\Exception\Session\SessionInvalidException;

class Kernel implements SessionContract
{
    protected Storage $storage;

    protected string $sessionName;

    protected const SESSION_PATTERN = '/^[a-zA-Z0-9_\.]{1,64}$/';

    public function __construct(string $sessionName, Storage $storage = null)
    {
        if ($this->isSessionKeyValid($sessionName) === false) {
            throw new SessionInvalidException("$sessionName is not a valid session name.");
        }

        $this->sessionName = $sessionName;
        $this->storage = $storage;
    }

    public function set(string $key, $value)
    {
        $this->ensureSessionKeyIsValid($key);

        $this->storage->setSession($key, $value);
    }

    public function setArray(string $key, $value)
    {
        $this->ensureSessionKeyIsValid($key);

        $this->storage->setArraySession($key, $value);
    }

    public function get(string $key, $default = null)
    {
        $this->ensureSessionKeyIsValid($key);

        return $this->storage->getSession($key, $default);
    }

    public function delete(string $key)
    {
        $this->ensureSessionKeyIsValid($key);

        return $this->storage->deleteSession($key);
    }

    public function invalid()
    {
        $this->storage->invalid();
    }

    public function flush(string $key, $default = null)
    {
        $this->ensureSessionKeyIsValid($key);

        $this->storage->flush($key, $default);
    }

    public function has(string $key): bool
    {
        $this->ensureSessionKeyIsValid($key);

        return $this->storage->hasSession($key);
    }

    protected function isSessionKeyValid(string $key)
    {
        return (preg_match(self::SESSION_PATTERN, $key) === 1);
    }

    protected function ensureSessionKeyIsValid($key)
    {
        if (!$this->isSessionKeyValid($key)) {
            throw new SessionInvalidException("$key is not a valid session key");
        }
    }

}
