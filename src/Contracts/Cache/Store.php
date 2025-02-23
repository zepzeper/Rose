<?php

namespace Rose\Contracts\Cache;

interface Store
{
    /**
     * Get an item from the cache.
     *
     * @param string $key
     * @return mixed
     */
    public function get($key);

    /**
     * Store an item in the cache.
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    public function put($key, $value, $ttl = null);

    /**
     * Remove an item from the cache.
     *
     * @param string $key
     * @return bool
     */
    public function forget($key);

    /**
     * Check if item exists in cache.
     *
     * @param string $key
     * @return bool
     */
    public function has($key);

    /**
     * Clear all items from the cache.
     *
     * @return bool
     */
    public function flush();
}

