<?php

namespace Rose\Session\Storage;

use Rose\Contracts\Session\Storage\Storage as StorageContract;

abstract class AbstractSessionStorage implements StorageContract
{

    protected array $options = [];

    public function __construct(array $options = [])
    {
        $this->options = $options;

        $this->__iniSet();

        if ($this->isSessionStarted()) {
            session_unset();
            session_destroy();
        }

        $this->start();
    }

    public function setSessionName(string $sessionName): void
    {
        session_name($sessionName);
    }

    public function getSessionName(): string
    {
        return session_name();
    }

    public function setSessionID(string $sessionID): void
    {
        session_id($sessionID);
    }

    public function getSessionID(): string
    {
        return session_id();
    }

    public function __iniSet()
    {
        ini_set('session.gc_maxlifetime', $this->options['gc_maxlifetime']);
        ini_set('session.gc_divisor', $this->options['gc_divisor']);
        ini_set('session.gc_probabillity', $this->options['gc_probabillity']);
        ini_set('session.cookie_lifetime', $this->options['cookie_lifetime']);
        ini_set('session.use_cookies', $this->options['use_cookies']);
    }

    public function isSessionStarted()
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public function startSession()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function start()
    {
        $this->setSessionName($this->options['session_name']);
        $domain = (isset($this->options['domain']) ? $this->options['domain'] : isset($_SERVER['SERVER_NAME']));
        $secure = $this->options['secure'] ?? (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

        session_set_cookie_params($this->options['lifetime'], $this->options['path'], $domain, $secure, $this->options['http_only'], $this->options['samesite'] ?? 'Lax');

        $this->startSession();
    }
}
