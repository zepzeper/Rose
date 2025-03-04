<?php

namespace Rose\Config;

use Rose\Support\Album\Arr;

class Repository
{
    protected $items = [];

    /**
     * @param array $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }
    
    public function has($key)
    {
        return Arr::has($this->items, $key);
    }

    /**
     * @param array|string $key
     * @param mixed        $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (is_array($key)) {
            return $this->getMany($key);
        }

        return Arr::get($this->items, $key, $default);
    }

    /**
     * @param array|string $key
     * @param mixed        $default
     *
     * @return void
     */
    public function set($key, $default = null)
    {
        $keys = is_array($key) ? $key : [$key => $default];

        foreach ($keys as $key => $value) {
            Arr::set($this->items, $key, $value);
        }
    }

    /**
     * @param array<string|int,mixed> $keys
     *
     * @return array<string,mixed>
     */
    public function getMany($keys)
    {
        $config = [];

        foreach ($keys as $key => $default) {

            if (is_numeric($key)) {
                [$key, $default] = [$default, null];
            }

            $config[$key] = Arr::get($this->items, $key, $default);
        }

        return $config;
    }

}
