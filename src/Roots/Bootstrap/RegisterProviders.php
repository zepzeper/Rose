<?php

namespace Rose\Roots\Bootstrap;

use Rose\Roots\Application;
use Rose\Support\ServiceProvider;

/**
 * The RegisterProviders class manages the registration of service providers
 * during application bootstrap. Service providers are the fundamental building
 * blocks of the framework, responsible for registering bindings, event listeners,
 * routes, and other pieces of functionality.
 * 
 * This class orchestrates three types of providers:
 * 1. Framework default providers (core functionality)
 * 2. Package providers (from third-party packages)
 * 3. Application providers (specific to your application)
 * 
 * Think of service providers as the building blocks that assemble your application.
 * This class acts as the construction foreman, ensuring all these blocks are 
 * put together in the right order and at the right time.
 */
class RegisterProviders
{
    /**
     * Holds providers that need to be merged into the configuration
     * before the provider registration process begins. This static
     * property allows providers to be collected across multiple
     * calls before the actual registration happens.
     * 
     * Think of this as a staging area where providers wait to be
     * processed during bootstrap.
     *
     * @var array
     */
    protected static $merge = [];

    /**
     * Stores the path to the bootstrap provider configuration file.
     * This file typically contains additional providers from installed
     * packages or application-specific providers that should be loaded
     * during bootstrap.
     * 
     * The path can be null if no additional providers need to be loaded
     * from a configuration file.
     *
     * @var string|null
     */
    protected static $bootstrapProviderPath;

    /**
     * Bootstrap the provider registration process for the application.
     * 
     * This method serves as the entry point for provider registration and:
     * 1. Checks if providers need to be merged (based on config caching)
     * 2. Merges additional providers if needed
     * 3. Triggers the actual provider registration
     * 
     * The cached configuration check prevents unnecessary merging when
     * the application is running with cached configurations.
     *
     * @param Application $app The application instance to bootstrap
     */
    public function bootstrap(Application $app)
    {
        // Only merge providers if configuration isn't cached
        if (! $app->bound('cached_config_loaded') || $app->make('cached_config_loaded') !== false) {
            $this->mergeAdditionalProviders($app);
        }

        // Register all configured providers with the application
        $app->registerConfiguredProviders();
    }

    /**
     * Merge additional providers into the application's configuration.
     * 
     * This method builds the final list of providers by combining:
     * 1. Framework default providers (foundational services)
     * 2. Manually merged providers (from application code)
     * 3. Package providers (from bootstrap provider file)
     * 
     * The method performs provider validation by checking class existence
     * and removes any invalid providers to prevent errors during registration.
     *
     * @param Application $app The application instance
     */
    protected function mergeAdditionalProviders(Application $app)
    {
        // Start with framework's default providers
        $defaultProviders = ServiceProvider::defaultProviders()->toArray();
    
        // Load and validate package providers from configuration
        $packageProviders = [];
        if (static::$bootstrapProviderPath && file_exists(static::$bootstrapProviderPath)) {
            $packageProviders = include static::$bootstrapProviderPath;
            
            // Remove any providers whose classes don't exist
            foreach ($packageProviders as $index => $provider) {
                if (!class_exists($provider)) {
                    unset($packageProviders[$index]);
                }
            }
        }

        // Merge all providers and update application configuration
        // Order matters here: defaults first, then merged, then package providers
        $app->make('config')->set(
            'app.providers',
            array_merge(
                $defaultProviders,
                static::$merge,
                array_values($packageProviders ?? []),
            ),
        );
    }

    /**
     * Add providers to be merged during registration.
     * 
     * This static method allows the application to queue up providers
     * for registration before the bootstrap process begins. It:
     * 1. Sets the bootstrap provider path for package providers
     * 2. Merges new providers with any existing ones
     * 3. Ensures providers are unique and valid
     * 
     * @param array       $providers             Providers to merge
     * @param string|null $bootstrapProviderPath Path to provider config
     */
    public static function merge(array $providers, ?string $bootstrapProviderPath = null)
    {
        static::$bootstrapProviderPath = $bootstrapProviderPath;
        
        // Merge, filter, and deduplicate providers
        static::$merge = array_values(
            array_filter(
                array_unique(
                    array_merge(static::$merge, $providers)
                )
            )
        );
    }

    /**
     * Reset the bootstrapper's static state.
     * 
     * This method provides a way to reset the static properties
     * to their initial state. This is particularly useful in:
     * - Testing scenarios
     * - When you need to restart the provider registration process
     * - Before handling a new request in long-running processes
     */
    public static function flushState()
    {
        static::$bootstrapProviderPath = null;
        static::$merge = [];
    }
}
