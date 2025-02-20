<?php

namespace Rose\Roots\Configuration;

use Rose\Roots\Application;
use Rose\Roots\Bootstrap\RegisterProviders;

/**
 * The ApplicationBuilder class provides a fluent interface for configuring and bootstrapping
 * the framework's application instance. It follows the Builder pattern to allow step-by-step
 * configuration of complex application setup.
 * 
 * Key responsibilities:
 * 1. Configuring core framework services
 * 2. Registering service providers
 * 3. Setting up the HTTP kernel
 * 4. Providing a clean API for application initialization
 * 
 * Example usage:
 * $app = (new ApplicationBuilder($container))
 *     ->withKernels()
 *     ->withProviders(['App\Providers\AuthServiceProvider'])
 *     ->create();
 */
class ApplicationBuilder
{
    /**
     * Create a new application builder instance.
     * 
     * The builder takes an Application instance that serves as the foundation
     * for the framework. This application instance typically contains the
     * service container and basic framework bootstrapping logic.
     * 
     * @param Application $app The base application instance to configure
     */
    public function __construct(protected Application $app)
    {
    }

    /**
     * Register the framework's kernel services.
     * 
     * This method sets up the HTTP kernel, which serves as the entry point
     * for all HTTP requests. The kernel is registered in the service container
     * as a singleton, ensuring only one instance exists throughout the request
     * lifecycle.
     * 
     * The HTTP kernel is responsible for:
     * - Processing incoming requests
     * - Running middleware
     * - Routing requests to controllers
     * - Handling responses
     * 
     * @return $this For method chaining
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
     * Register additional service providers with the application.
     * 
     * Service providers are the primary way to bootstrap and configure
     * framework services. This method allows registration of:
     * - Custom application service providers
     * - Third-party package service providers
     * - Framework core service providers
     * 
     * When $withBootstrapProviders is true, it will also load providers
     * from the framework's bootstrap providers configuration file.
     * 
     * Example providers array:
     * [
     *     App\Providers\AuthServiceProvider::class,
     *     App\Providers\RouteServiceProvider::class,
     *     ThirdParty\Package\ServiceProvider::class
     * ]
     * 
     * @param  array  $providers              Array of service provider class names
     * @param  bool   $withBootstrapProviders Whether to include bootstrap providers
     * @return $this                          For method chaining
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
     * Finalize the application configuration and return the configured instance.
     * 
     * This method marks the end of the building process and returns the fully
     * configured application instance. At this point:
     * - All requested services have been registered
     * - Service providers have been loaded
     * - The kernel has been configured
     * - The application is ready to handle requests
     * 
     * @return Application The configured application instance
     */
    public function create(): Application
    {
        return $this->app;
    }
}
