<?php

namespace Rose\Roots;

use Closure;
use Composer\Autoload\ClassLoader;
use Rose\Container\Container;
use Rose\Contracts\Http\Kernel;
use Rose\Contracts\Roots\Application as ApplicationContract;
use Rose\Events\EventServiceProvider;
use Rose\Roots\Configuration\ApplicationBuilder;
use Rose\Routing\RouterServiceProvider;
use Rose\Support\Providers\RouteServiceProvider;
use Rose\Support\Album\Collection;
use Rose\Support\ServiceProvider;
use Rose\System\FileSystem;
use Symfony\Component\HttpFoundation\Request;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

/**
 * The Application class is the heart of your framework, serving as both a service container
 * and the central coordination point for all framework components. It manages the entire
 * lifecycle of your application, from bootstrap to shutdown.
 * 
 * Key responsibilities include:
 * 1. Service Container Management
 * 2. Service Provider Coordination
 * 3. Path Resolution
 * 4. Environment Configuration
 * 5. Application Bootstrapping
 * 
 * By extending Container, the Application class itself acts as the main service container,
 * allowing for dependency injection throughout your application.
 */

class Application extends Container implements ApplicationContract
{
    /** 
     * The current framework version. Using semantic versioning to track releases.
     */
    public const VERSION = '0.1.alpha';

    /**
     * Crucial application paths that define the structure of your framework.
     * These paths help locate different components and resources.
     */
    protected string $appPath;
    protected string $basePath;
    protected string $bootstrapPath;

    /**
     * Environment configuration files that determine application behavior
     * across different environments (development, staging, production).
     */
    protected string $environmentFile = '.env';
    protected string $enviromentPath = __DIR__ . '/../../';

    /**
     * The service container instance. While the Application class itself
     * is a container, this property can hold a reference to itself or
     * a different container implementation.
     */
    protected Container $container;

    /**
     * Tracks all registered service providers. These providers are the building
     * blocks that add functionality to your framework.
     */
    protected array $serviceProviders = [];

    /**
     * Path to the cached configuration file for improved performance
     * in production environments.
     */
    protected string $getCachedConfigPath;

    /**
     * State flags to track the application's bootstrap and boot status.
     * These prevent duplicate bootstrapping or booting operations.
     */
    private bool $hasBeenBootstrapped = false;
    private bool $booted = false;

    /*
     *
     */
    protected array $bootingCallbacks = [];
    protected array $bootedCallbacks = [];

    /**
     * Initialize a new application instance.
     * 
     * This constructor sets up the fundamental pieces needed for the application:
     * 1. Establishes the application's base path
     * 2. Sets up basic service container bindings
     * 3. Registers core service providers
     * 4. Configures container aliases for easier service resolution
     * 
     * @param string|null $basePath Optional base path for the application
     */
    public function __construct(?string $basePath = null)
    {
        if ($basePath) {
            $this->setBasePath($basePath ?? self::inferBasePath());
        }

        $this->registerBaseBindings();
        $this->registerBaseServiceProviders();
        $this->registerCoreContainerAliases();
    }

    /**
     * Boot all registered service providers.
     * 
     * The boot process runs after all providers have been registered,
     * allowing providers to utilize services from other providers.
     * This two-step process (register then boot) ensures proper
     * service initialization order.
     */
    public function boot()
    {
        if ($this->isBooted()) {
            return;
        }

        $this->fireBootCallbacks($this->bootingCallbacks);

        // Boot each service provider
        array_walk(
            $this->serviceProviders,
            function ($q) {
                $this->bootProvider($q);
            }
        );

        $this->fireBootCallbacks($this->bootedCallbacks);

        $this->booted = true;
    }

    protected function fireBootCallbacks(array &$callbacks)
    {
        $count = 0;

        while ($count < count($callbacks))
        {
            $callbacks[$count]($this);
            $count++;
        }
    }

    protected function bootProvider(ServiceProvider $provider)
    {
        $provider->callBootingCallbacks();

        if (method_exists($provider, 'boot')) {
            $this->call([$provider, 'boot']);
        }

        $provider->callBootedCallbacks();
    }

    public function isBooted()
    {
        return $this->booted;
    }

    public function booting(callable $callback)
    {
        $this->bootingCallbacks[] = $callback;
    }

    public function booted(callable $callback)
    {
        $this->bootedCallbacks[] = $callback;

        if ($this->isBooted())
        {
            $callback($this);
        }

    }

    /**
     * Handle the incoming HTTP request and send the response to the browser.
     *
     * @param  Request  $request
     * @return void
     */
    public function handleRequest(Request $request): void
    {
        $kernel = $this->make(Kernel::class);

        $response = $kernel->handle($request)->send();

        $kernel->terminate($request, $response);
    }

    /**
     * Bootstrap the application with a set of bootstrapper classes.
     * 
     * Bootstrapping is the initial setup phase where core framework
     * services are prepared. This process includes:
     * 1. Loading environment variables
     * 2. Loading configuration
     * 3. Setting up error handling
     * 4. Registering facades
     * 
     * Events are dispatched before and after each bootstrapper,
     * allowing for fine-grained control of the bootstrap process.
     * 
     * @param array $bootstrappers Array of bootstrapper classes to run
     */
    public function bootstrapWith($bootstrappers)
    {
        $this->hasBeenBootstrapped = true;

        foreach ($bootstrappers as $bootstrapper) {
            // Dispatch pre-bootstrap event
            $this['events']->dispatch('boostrapping: ' . $bootstrapper, [$this]);

            // Run the bootstrapper
            $this->make($bootstrapper)->bootstrap($this);

            // Dispatch post-bootstrap event
            $this['events']->dispatch('bootsrapped: ' . $bootstrapper, [$this]);
        }
    }

    /**
     * Determine if the application has been bootstrapped before.
     *
     * @return bool
     */
    public function hasBeenBootstrapped()
    {
        return $this->hasBeenBootstrapped;
    }

    /**
     * The Path Management system is responsible for handling all directory paths in your application.
     * Think of it as your framework's GPS - it helps locate all the important directories and files
     * your application needs to function.
     * 
     * For example, when your application needs to:
     * - Load a configuration file
     * - Store cached data
     * - Access database files
     * - Find bootstrap files
     * 
     * The path management system knows exactly where to look.
     */

    /**
     * Sets the base path for the entire application.
     * This is like setting the "home address" for your application - 
     * all other paths will be calculated relative to this one.
     */
    public function setBasePath(string $basePath): self
    {
        // Clean up the path by removing trailing slashes
        $this->basePath = rtrim($basePath, '\/');

        // Register all important paths in the container
        $this->bindPathsInContainer();

        return $this;
    }

    /**
     * Makes all important paths available throughout the application
     * by registering them in the service container.
     * 
     * This is like creating a directory of important locations that
     * any part of your application can look up when needed.
     */
    protected function bindPathsInContainer(): void
    {
        $this->instance('path.base', $this->basePath());
        $this->instance('path.config', $this->configPath());
        $this->instance('path.database', $this->databasePath());
        $this->instance('path.storage', $this->storagePath());

        $this->useBootstrapPath($this->basePath('bootstrap'));
    }

    /**
     * Get the base path of the installation.
     *
     * @param  string $path
     * @return string
     */
    public function basePath($path = '')
    {
        return $this->joinPaths($this->basePath, $path);
    }

    /**
     * Each path-related method follows a consistent pattern:
     * 1. Takes an optional sub-path
     * 2. Combines it with the appropriate base directory
     * 3. Returns the full, normalized path
     * 
     * This creates a predictable directory structure:
     * 
     * your-app/
     * ├── config/         ← configPath()
     * ├── database/       ← databasePath()
     * ├── storage/        ← storagePath()
     * └── bootstrap/      ← bootstrapPath()
     */
    public function configPath($path = '')
    {
        // For example: configPath('app.php') returns '/your-app/config/app.php'
        return $this->joinPaths($this->basePath, 'config' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }

    public function databasePath($path = '')
    {
        // For example: databasePath('migrations') returns '/your-app/database/migrations'
        return $this->joinPaths($this->basePath, 'database' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }

    /**
     * Get the path to the storage directory.
     *
     * @param  string $path
     * @return string
     */
    public function storagePath($path = '')
    {
        return $this->joinPaths($this->basePath, 'storage' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }

    /**
     * Get the path to the bootstrap directory.
     *
     * @param  string $path
     * @return string
     */
    public function bootstrapPath($path = '')
    {
        return $this->joinPaths($this->bootstrapPath, $path);
    }

    public function getBootstrapProvidersPath()
    {
        return $this->bootstrapPath('providers.php');
    }

    public function useBootstrapPath(string $path): self
    {
        $this->bootstrapPath = $path;
        return $this;
    }

    /**
     * The Service Provider system is the backbone of your framework's modularity.
     * Think of service providers as plugins that add specific features to your application.
     * 
     * For example:
     * - DatabaseServiceProvider adds database functionality
     * - EventServiceProvider adds event handling
     * - SessionServiceProvider adds session management
     */
    public function registerConfiguredProviders()
    {
        // Get the list of providers from configuration
        $configProviders = $this->make('config')->get('app.providers');
    
        // Create a collection for easier manipulation
        $providers = (new Collection($configProviders));
    
        // Create a new provider repository and load all providers
        (new ProviderRepository($this, new FileSystem(), ''))
            ->load($providers->flatten()->toArray());
    }

    /**
     * Registers individual service providers with the application.
     * This is like plugging in a new component to your application.
     * 
     * The process ensures that:
     * 1. The provider is properly instantiated
     * 2. Its register method is called
     * 3. It's tracked for later booting
     * 4. Duplicates are prevented
     */
    public function register($provider, $force = false)
    {
        // Convert string class names to actual provider instances
        if (is_string($provider)) {
            $provider = $this->resolveProvider($provider);
        }

        // Call the provider's register method if it exists
        if (method_exists($provider, 'register')) {
            $provider->register();
        }

        // Prevent duplicate providers
        if (!in_array($provider, $this->serviceProviders, true)) {
            $this->serviceProviders[] = $provider;
        }

        return $provider;
    }

    protected function registerBaseServiceProviders(): void
    {
        $this->registerErrorHandler();
        $this->register(new EventServiceProvider($this));
        $this->register(new RouterServiceProvider($this));
    }

    // Region: Configuration
    public function getCachedConfigPath()
    {
        return '';
    }

    public function configurationIsCached()
    {
        return false;
    }

    public function environmentPath()
    {
        return $this->enviromentPath ?: $this->basePath;
    }

    public function environmentFile()
    {
        return $this->environmentFile;
    }

    public function detectEnviroment(?Closure $callback = null)
    {

    }

    /**
     * The Environment Management system helps your application behave differently
     * based on where it's running (development, staging, production, etc.).
     * 
     */
    public function environment(...$environments)
    {
        // Check if the current environment matches any of the provided ones
        return env('APP_ENV') == $environments;
    }

    protected function registerErrorHandler(): void
    {
        // Set up pretty error pages for better debugging
        (new Run())->pushHandler(new PrettyPageHandler())->register();
    }

    // Region: Utilities
    public static function inferBasePath(): string
    {
        return $_ENV['APP_BASE_PATH'] ?? dirname(
            array_values(
                array_filter(
                    array_keys(ClassLoader::getRegisteredLoaders()),
                    fn ($path) => !str_contains($path, '/vendor/')
                )
            )[0]
        );
    }

    public function joinPaths(string $basePath, string $path = ''): string
    {
        return join_paths($basePath, $path);
    }

    // Region: Internal Helpers
    protected function registerBaseBindings(): void
    {
        static::setInstance($this);
        $this->instance('app', $this);
        $this->instance(Container::class, $this);
    }

    /**
     * Container aliases provide convenient shortcuts to commonly used services.
     */
    protected function registerCoreContainerAliases(): void
    {
        $aliasesArray = [
            'app' => [self::class, Container::class, Application::class],
            'events' => [\Rose\Events\Dispatcher::class, \Rose\Contracts\Events\Dispatcher::class],
            'encrypter' => [\Rose\Encryption\Encryption::class],
            'router' => [\Rose\Routing\Router::class, \Rose\Contracts\Routing\Router::class],
            'session' => [\Rose\Session\Manager\SessionManager::class],
            'session.store' => [\Rose\Session\Storage\NativeSessionHandler::class, \Rose\Session\Storage\AbstractSessionHandler::class],
        ];

        // Register each alias
        foreach ($aliasesArray as $key => $alias) {
            foreach ($alias as $alias) {
                $this->alias($key, $alias);
            }
        }
    }

    /**
     * Resolve a service provider instance from the class name.
     *
     * @param  string $provider
     * @return \Rose\Support\ServiceProvider
     */
    public function resolveProvider($provider): ServiceProvider
    {
        return new $provider($this);
    }

    public static function configure(?string $basePath = null): ApplicationBuilder
    {
        return (new ApplicationBuilder(new static($basePath ?? self::inferBasePath())))
            ->withKernels()
            ->withProviders();
    }

    /**
     * Get the version number of the application.
     *
     * @return string
     */
    public function version()
    {
        return self::VERSION;
    }
}
