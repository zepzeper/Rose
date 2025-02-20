<?php

namespace Rose\Session\Storage;

use Rose\Exceptions\Encryption\DecryptionFailureException;
use Rose\Exceptions\Encryption\EncryptionFailureException;
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
        // Force output buffering in container environment
        if (ob_get_level() === 0) {
            ob_start();
        }
        // Debug point 1: Check session status
        $status = session_status();
        error_log("Session status at configure(): $status");

        // Debug point 2: Check if headers have been sent
        if (headers_sent($file, $line)) {
            error_log("Headers already sent in $file on line $line");
        }

        // Debug point 3: Check current cookie parameters
        $currentParams = session_get_cookie_params();
        error_log("Current cookie params: " . json_encode($currentParams));

        foreach ($this->options as $key => $value) {
            if (str_starts_with($key, 'session.')) {
                ini_set($key, $value);
            }
        }

        try {
            session_set_cookie_params(
                $this->options['lifetime'],
                $this->options['path'],
                $this->options['domain'],
                $this->options['secure'],
                $this->options['http_only']
            );
            error_log("Successfully set cookie parameters");
        } catch (\Exception $e) {
            error_log("Failed to set cookie parameters: " . $e->getMessage());
        }
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
            fn ($value) => $this->unserialize($value),
            $_SESSION
        );
    }

    public function regenerate(): bool
    {
        return session_regenerate_id(true);
    }
}
