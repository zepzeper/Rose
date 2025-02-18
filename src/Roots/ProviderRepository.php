<?php

namespace Rose\Roots;

use \Rose\Contracts\Roots\Application as ApplicationContract;
use \Rose\System\FileSystem;

class ProviderRepository
{
    /**
     * @var \Rose\Contract\Roots\Application;
     */
    protected $app;

    protected $files;

    protected $manifest;

    public function __construct(ApplicationContract $app, FileSystem $files, string $manifest)
    {
        $this->app = $app;
        $this->files = $files;
        $this->manifest = $manifest;
    }

    /**
     *
     * @param  array $providers
     * @return void
     */
    public function load($providers)
    {
        foreach ($providers as $provider) {
            $this->app->register($provider);
        }
    }

    /**
     *
     * @param  string $provider
     * @return \Rose\Support\ServiceProvider
     */
    public function createProvider($provider)
    {
        return new $provider($this->app);
    }
}
