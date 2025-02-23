<?php

namespace Rose\Contracts\Cache;

interface CacheManager
{
    /**
     * Get a cache store instance by name.
     *
     * @param string|null $name
     * @return \Rose\Contracts\Cache\Store
     */
    public function store($name = null);

    /**
     * Get a cache driver instance.
     *
     * @param string|null $driver
     * @return \Rose\Contracts\Cache\Store
     */
    public function driver($driver = null);

    /**
     * Register a new cache driver.
     *
     * @param string $driver
     * @param \Closure $callback
     * @return $this
     */
    public function extend($driver, \Closure $callback);
}
