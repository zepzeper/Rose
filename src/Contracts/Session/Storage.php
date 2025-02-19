<?php

namespace Rose\Contracts\Session;

interface Storage
{
    public function get(string $key, $default = null);
    public function set(string $key, $value): void;
    public function has(string $key): bool;
    public function remove(string $key): void;
    public function clear(): void;
    public function all(): array;
    public function regenerate(): bool;
}
