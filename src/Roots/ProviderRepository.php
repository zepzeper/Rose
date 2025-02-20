<?php

namespace Rose\Roots;

use Rose\Contracts\Roots\Application as ApplicationContract;
use Rose\System\FileSystem;

/**
 * The ProviderRepository serves as the central manager for service providers in your framework.
 * Think of it as a librarian who knows about all the available services your application can use
 * and helps set them up correctly.
 * 
 * Service providers are the backbone of your framework's bootstrapping process - they register
 * and configure the various services your application needs to run. This repository helps
 * manage these providers efficiently and reliably.
 * 
 * The repository handles three main responsibilities:
 * 1. Storing information about available service providers
 * 2. Loading and initializing providers when needed
 * 3. Managing the provider registration process
 */
class ProviderRepository
{
    /**
     * The application instance represents your entire framework.
     * We store it here because service providers need access to the application
     * container to register their services.
     * 
     * @var \Rose\Contract\Roots\Application
     */
    protected $app;

    /**
     * The filesystem instance helps us read and write provider manifests
     * and other related files. This abstraction allows for different
     * storage implementations (local files, cloud storage, etc.).
     */
    protected $files;

    /**
     * The manifest path stores the location of the service provider manifest file.
     * This file acts like a catalog, keeping track of all registered providers
     * and their loading order.
     */
    protected $manifest;

    /**
     * Create a new provider repository instance.
     * 
     * This constructor sets up everything the repository needs to manage providers.
     * It's like preparing a librarian with their catalog (manifest) and the tools
     * they need (filesystem) to organize the library (application).
     * 
     * @param ApplicationContract $app      The application instance
     * @param FileSystem         $files    The filesystem instance
     * @param string            $manifest The path to the provider manifest
     */
    public function __construct(ApplicationContract $app, FileSystem $files, string $manifest)
    {
        $this->app = $app;
        $this->files = $files;
        $this->manifest = $manifest;
    }

    /**
     * Load an array of service providers.
     * 
     * This method is like a librarian going through a list of books and putting
     * each one in its proper place. For each provider in the list, it:
     * 1. Creates an instance of the provider
     * 2. Registers it with the application
     * 3. Ensures all providers are loaded in the correct order
     * 
     * For example, if you have providers that depend on each other:
     * - DatabaseServiceProvider must load before
     * - AuthenticationServiceProvider, which must load before
     * - RoutingServiceProvider
     * 
     * This method ensures they load in the correct sequence.
     *
     * @param array $providers Array of provider class names to load
     */
    public function load($providers)
    {
        foreach ($providers as $provider) {
            $this->app->register($provider);
        }
    }

    /**
     * Create a new instance of a service provider.
     * 
     * This method acts like a factory for service providers. It:
     * 1. Takes the provider's class name
     * 2. Creates a new instance
     * 3. Injects the application instance
     * 
     * This process ensures each provider has access to the application
     * container and can register its services properly.
     * 
     * For example:
     * $provider = $repository->createProvider(DatabaseServiceProvider::class);
     * // Creates new DatabaseServiceProvider with access to the application
     *
     * @param string $provider The class name of the provider to create
     * @return \Rose\Support\ServiceProvider The instantiated provider
     */
    public function createProvider($provider)
    {
        return new $provider($this->app);
    }
}
