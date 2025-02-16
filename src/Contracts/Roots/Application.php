<?php

namespace Rose\Contracts\Roots;

use Rose\Contracts\Container\Container;

interface Application extends Container
{
    /**
     * Get the version number of the application.
     *
     * @return string
     */
    public function version();

    /**
     * Get the base path of the installation.
     *
     * @param  string  $path
     * @return string
     */
    public function basePath($path = '');

    /**
     * Get the path to the bootstrap directory.
     *
     * @param  string  $path
     * @return string
     */
    public function bootstrapPath($path = '');

    /**
     * Get the path to the application configuration files.
     *
     * @param  string  $path
     * @return string
     */
    public function configPath($path = '');

    /**
     * Get the path to the database directory.
     *
     * @param  string  $path
     * @return string
     */
    public function databasePath($path = '');

    /**
     * Get the path to the storage directory.
     *
     * @param  string  $path
     * @return string
     */
    public function storagePath($path = '');

    /**
     * Get or check the current application environment.
     *
     * @param  string|array  ...$environments
     * @return string|bool
     */
    public function environment(...$environments);

    /**
     * Register all of the configured providers.
     *
     * @return void
     */
    public function registerConfiguredProviders();

    /**
     * Register a service provider with the application.
     *
     * @param  \Rose\Support\ServiceProvider|string  $provider
     * @param  bool  $force
     * @return \Rose\Support\ServiceProvider
     */
    public function register($provider, $force = false);

    /**
     * Resolve a service provider instance from the class name.
     *
     * @param  string  $provider
     * @return \Rose\Support\ServiceProvider
     */
    public function resolveProvider($provider);

    /**
     * Boot the application's service providers.
     *
     * @return void
     */
    public function boot();

    /**
     * Run the given array of bootstrap classes.
     *
     * @param  array  $bootstrappers
     * @return void
     */
    public function bootstrapWith(array $bootstrappers);

    /**
     * Determine if the application has been bootstrapped before.
     *
     * @return bool
     */
    public function hasBeenBootstrapped();
}
