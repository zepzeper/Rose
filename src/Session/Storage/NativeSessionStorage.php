<?php

namespace Rose\Session\Storage;

use Rose\Exception\Encryption\DecryptionFailureException;
use Rose\Exception\Encryption\Security\EncryptionFailureException;
use Rose\Roots\Application;
use Rose\Security\Encryption;

class NativeSessionStorage extends AbstractSessionStorage
{
    public function __construct(Application $app, private Encryption $encryptor)
    {
        $config = $app->make('app')->get('session');

        parent::__construct($app);
    }

    public function setSession(string $key, $value): void
    {
        try {
            $_SESSION[$key] = $this->encryptor->encrypt(serialize($value));
        } catch (EncryptionFailureException) {
            throw new EncryptionFailureException("Failed to encrypt $key.");
        }
    }

    public function setArraySession(string $key, $value)
    {
        $_SESSION[$key][] = $value;
    }

    public function getSession(string $key, $default = null)
    {
        if (!$this->hasSession($key)) {
            return $default;
        }

        try {
            return unserialize($this->encryptor->decrypt($_SESSION[$key]));
        } catch (\Exception $e) {
            $this->invalid();
            return $default;
        }
    }

    public function deleteSession(string $key): void
    {
        if ($this->hasSession($key)) {
            unset($_SESSION[$key]);
        }
    }

    public function invalid()
    {
        $_SESSION = array();

        if (ini_set('session.use_cookies', null)) {
            $params = session_get_cookie_params();
            setcookie($this->getSessionName(), '', time() - $params['lifetime'], $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_unset();
        session_destroy();

        $this->regenerate();
    }

    public function flush(string $key, $default = null)
    {
        if ($this->hasSession($key)) {
            $value = $_SESSION[$key];
            $this->deleteSession($key);
            return $value;
        }

        return $default;
    }

    public function regenerate(): void
    {
        session_regenerate_id(true); // Destroy the old session_id.
    }

    public function hasSession(string $key): bool
    {
        return isset($_SESSION[$key]);
    }
}
