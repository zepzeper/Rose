<?php

namespace Rose\Contracts\Session;

interface Kernel
{
    public function set(string $key, $value);

    public function setArray(string $key, $value);

    public function get(string $key, $value);

    public function delete(string $key);

    public function invalid();

    public function flush(string $key, $value);

    public function has(string $key): bool;
}
