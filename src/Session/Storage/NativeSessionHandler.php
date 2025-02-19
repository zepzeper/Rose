<?php

namespace Rose\Session\Storage;

use Rose\Exception\Encryption\DecryptionFailureException;
use Rose\Exception\Encryption\EncryptionFailureException;
use Rose\Roots\Application;
use Rose\Encryption\Encryption;

class NativeSessionHandler extends AbstractSessionHandler
{
    public function __construct($app, $encryptor, array $options = [])
    {
        parent::__construct($app, $encryptor, $options);
        $this->start();
    }

    protected function start(): void
    {
        $this->configure();
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    protected function configure(): void
    {
        foreach ($this->options as $key => $value) {
            if (str_starts_with($key, 'session.')) {
                ini_set($key, $value);
            }
        }

        session_set_cookie_params(
            $this->options['lifetime'],
            $this->options['path'],
            $this->options['domain'],
            $this->options['secure'],
            $this->options['http_only']
        );
    }

    public function get(string $key, $default = null)
    {
        if (!$this->has($key)) {
            return $default;
        }

        try {
            return $this->unserialize($_SESSION[$key]);
        } catch (\Exception $e) {
            $this->clear();
            return $default;
        }
    }

    public function set(string $key, $value): void
    {
        try {
            $_SESSION[$key] = $this->serialize($value);
        } catch (EncryptionFailureException) {
            throw new EncryptionFailureException("Failed to encrypt $key.");
        }
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function clear(): void
    {
        $_SESSION = [];
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        $this->start();
    }

    public function all(): array
    {
        return array_map(
            fn($value) => $this->unserialize($value),
            $_SESSION
        );
    }

    public function regenerate(): bool
    {
        return session_regenerate_id(true);
    }
}
