<?php

namespace Rose\Contracts\Session\Storage;

interface Storage
{
    public function setSessionName(string $sessionName): void;

    public function getSessionName(): string;

    public function setSessionID(string $sessionID): void;

    public function getSessionID(): string;

    public function setSession(string $key, $value);

    public function setArraySession(string $key, $value);

    public function getSession(string $key, $default = null);

    public function deleteSession(string $key): void;

    public function invalid();

    public function flush(string $key, $default = null);

    public function regenerate(): void;

    public function hasSession(string $key): bool;
}
