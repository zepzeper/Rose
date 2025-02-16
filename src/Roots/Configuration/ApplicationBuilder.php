<?php

namespace Rose\Roots\Configuration;

use Rose\Roots\Application;
use Rose\Roots\Bootstrap\RegisterProviders;

class ApplicationBuilder
{
    /**
     * Create a new application builder instance.
     */
    public function __construct(protected Application $app)
    {
    }

    /**
     * @return $this
     */
    public function withKernels(): ApplicationBuilder
    {
        $this->app->singleton(
            \Rose\Contracts\Http\Kernel::class,
            \Rose\Roots\Http\Kernel::class,
        );

        return $this;
    }

    /**
     * Register additional service providers.
     *
     * @param  array  $providers
     * @param  bool  $withBootstrapProviders
     * @return $this
     */
    public function withProviders(array $providers = [], bool $withBootstrapProviders = true): ApplicationBuilder
    {
        RegisterProviders::merge(
            $providers,
            $withBootstrapProviders
                ? $this->app->getBootstrapProvidersPath()
                : null
        );

        return $this;
    }

    /**
     * Get the application instance.
     *
     * @return \Rose\Foundation\Application
     */
    public function create(): Application
    {
        return $this->app;
    }
}
