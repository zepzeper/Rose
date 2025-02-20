<?php

namespace Rose\Support;

use Closure;
use Rose\Roots\Application;
use RuntimeException;

abstract class ServiceProvider
{
    protected Application $app;

    protected array $publishes = [];

    /*
     *
     */
    protected array $bootingProviders = [];
    protected array $bootedProviders = [];

    /**
     * @param Application $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Merge the given configuration with the existing configuration.
     *
     * @param  string $path
     * @param  string $key
     * @return void
     */
    protected function mergeConfigFrom(string $path, string $key)
    {

        if (!file_exists($path)) {
            throw new RuntimeException("Config file in $path not found.");
        }

        $config = $this->app->make('app');

        $merged = array_merge(
            include $path,
            $config->get($key, [])
        );

        $config->set($merged);
    }

    /**
     * Register paths to be published by the publish command.
     *
     * @param  array  $paths
     * @param  string $group
     * @return void
     */
    protected function publishes(array $paths, $group = null)
    {
        $this->ensurePublishArrayInitialized($group);

        $this->publishes[$group] = array_merge(
            $this->publishes[$group] ?? [],
            $paths
        );
    }

    /**
     * Get the paths to publish.
     *
     * @param  string|null $group
     * @return array
     */
    public function pathsToPublish($group = null)
    {
        if ($group) {
            return $this->publishes[$group] ?? [];
        }

        return $this->publishes;
    }
    /**
     * @return DefaultProviders
     */
    public static function defaultProviders()
    {
        return new DefaultProviders();
    }

    /**
     * Ensure the publishes array is initialized for the given group.
     *
     * @param  string $group
     * @return void
     */
    private function ensurePublishArrayInitialized($group)
    {
        if (!array_key_exists($group, $this->publishes)) {
            $this->publishes[$group] = [];
        }
    }

    /**
     * Register a booting callback to be run before the "boot" method is called.
     *
     * @param  Closure  $callback
     * @return void
     */
    public function booting(Closure $callback): void
    {
        $this->bootingProviders[] = $callback;
    }

    /**
     * Register a booted callback to be run after the "boot" method is called.
     *
     * @param  Closure  $callback
     * @return void
     */
    public function booted(Closure $callback): void
    {
        $this->bootedProviders[] = $callback;
    }

    public function callBootingCallbacks()
    {
        $count = 0;

        while($count < count($this->bootingProviders))
        {
            $this->app->call($this->bootingProviders[$count]);
            $count++;
        }
    }

    public function callBootedCallbacks()
    {
        $count = 0;

        while($count < count($this->bootedProviders))
        {
            $this->app->call($this->bootedProviders[$count]);
            $count++;
        }
    }


    /**
     * Register any application services.
     *
     * @return void
     */
    abstract public function register();
}
