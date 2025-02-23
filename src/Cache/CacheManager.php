<?php

namespace Rose\Cache;

use Closure;
use Rose\Contracts\Cache\Store;
use Rose\Roots\Application;
use Rose\Contracts\Cache\CacheManager as CacheContract;

class CacheManager implements CacheContract
{

    /**
     * @var Application $app
     */
    protected $app;

    /**
    * array of resolved cache stores.
    * 
    * @var array $stores
    */
    protected $stores = [];

    /**
     * @var array<string,Closure>
     */
    private array $customDriver = [];
    /**
     * @param mixed $app
     */
    public function __construct($app) {
        $this->app = $app;
    }

    /**
     * Get a cache store instance by name.
     *
     * @param string|null $name
     * @return \Rose\Contracts\Cache\Store
     */
    public function store($name = null)
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->stores[$name] ?? ($this->stores[$name] = $this->resolve($name));
    }

    /**
     * @param string|null $driver
     * @return \Rose\Contracts\Cache\Store
     */
    public function driver($driver = null): Store
    {
        return $this->store($driver);
    }

    /**
     *
     * @param string $driver
     * @param Closure $callback
     * @return CacheManager;
     */
    public function extend($driver, Closure $callback): CacheManager
    {
        $this->customDriver[$driver] = $callback;

        return $this;
    }

    /**
     * @param string $name
     */
    protected function resolve($name)
    {
        $config = $this->getConfig($name);

        if (isset($this->customDriver[$name]))
        {
            return $this->callCustomCreator($config['driver'], $config);
        }

        $driverMethod = 'create' . ucfirst($config['driver']) . 'Driver';

        if (method_exists($this, $driverMethod))
        {
            return $this->$driverMethod($config);
        }

        throw new \InvalidArgumentException("Driver [{$config['driver']}] is not supported.");
    }

    /**
     * @param array $config
     */
    protected function createFileDriver($config)
    {
        return new FileStore(
            $this->app->make('files'),
            $config['path'] ?? $this->app->cachedConfigPath()
        );
    }

    /**
     * Get the cache configuration.
     *
     * @param string $name
     * @return array
     */
    protected function getConfig($name)
    {
        // Get cache config from your config files
        return $this->app['config']->get("cache.stores.{$name}") ?? [
            'driver' => 'file',
            'path' => $this->app->cachedConfigPath(),
        ];
    }

    protected function getDefaultDriver()
    {
        return $this->app['config']->get('cache.default');
    }
}
