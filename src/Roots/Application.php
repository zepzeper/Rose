<?php

namespace Rose\Roots;

use Closure;
use Composer\Autoload\ClassLoader;
use Rose\Container\Container;
use Rose\Contracts\Roots\Application as ApplicationContract;
use Rose\Events\EventServiceProvider;
use Rose\Roots\Bootstrap\RegisterProviders;
use Rose\Roots\Configuration\ApplicationBuilder;
use Rose\Session\SessionServiceProvider;
use Rose\Support\Album\Collection;
use Rose\Support\ServiceProvider;
use Rose\System\FileSystem;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

class Application extends Container implements ApplicationContract
{
    public const VERSION = '0.1.alpha';

    protected string $appPath;

    protected string $basePath;

    protected string $bootstrapPath;

    protected string $environmentFile = '.env';

    protected string $enviromentPath = __DIR__ . '/../../';

    protected Container $container;

    protected array $serviceProviders = [];

    protected string $getCachedConfigPath;

    private bool $hasBeenBootstrapped = false;

    private bool $booted = false;

    // Region: Core Application Setup
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
     * Boot the application's service providers.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->isBooted()) {
            return;
        }

        array_walk(
            $this->serviceProviders,
            function ($q) {
                $this->bootProvider($q);
            }
        );

        $this->booted = true;
    }

    protected function bootProvider(ServiceProvider $provider)
    {
        if (method_exists($provider, 'boot')) {
            $this->call([$provider, 'boot']);
        }

    }

    public function isBooted()
    {
        return $this->booted;
    }

    public static function configure(?string $basePath = null): ApplicationBuilder
    {
        return (new ApplicationBuilder(new static($basePath ?? self::inferBasePath())))
            ->withKernels()
            ->withProviders();
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
     * Run the given array of bootstrap classes.
     *
     * @param  array $bootstrappers
     * @return void
     */
    public function bootstrapWith($bootstrappers)
    {
        $this->hasBeenBootstrapped = true;

        foreach ($bootstrappers as $bootstrapper) {
            $this['events']->dispatch('boostrapping: ' . $bootstrapper, [$this]);

            $this->make($bootstrapper)->bootstrap($this);

            $this['events']->dispatch('bootsrapped: ' . $bootstrapper, [$this]);
        }
    }

    // Region: Path Management
    public function setBasePath(string $basePath): self
    {
        $this->basePath = rtrim($basePath, '\/');

        $this->bindPathsInContainer();

        return $this;
    }

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
     * Get the path to the application configuration files.
     *
     * @param  string $path
     * @return string
     */
    public function configPath($path = '')
    {
        return $this->joinPaths($this->basePath, 'config' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }

    /**
     * Get the path to the database directory.
     *
     * @param  string $path
     * @return string
     */
    public function databasePath($path = '')
    {
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

    // Region: Service Providers

    /**
     * Register all of the configured providers.
     *
     * @return void
     */
    public function registerConfiguredProviders()
    {
        $configProviders = $this->make('config')->get('app.providers');
    
        $providers = (new Collection($configProviders));
            //->partition(fn ($provider) => str_starts_with($provider, 'Rose\\'));
    
        (new ProviderRepository($this, new FileSystem(), ''))
        ->load($providers->flatten()->toArray());
    }

    protected function registerBaseServiceProviders(): void
    {
        $this->register(new EventServiceProvider($this));
        $this->registerErrorHandler();
    }


    protected function registerErrorHandler(): void
    {
        //if ($this->environment('production')) {
        //    return;
        //}

        (new Run())->pushHandler(new PrettyPageHandler());
    }

    /**
     * Register a service provider with the application.
     *
     * @param  \Rose\Support\ServiceProvider|string $provider
     * @param  bool                                 $force
     * @return \Rose\Support\ServiceProvider
     */
    public function register($provider, $force = false)
    {
        if (is_string($provider)) {
            $provider = $this->resolveProvider($provider);
        }

        if (method_exists($provider, 'register')) {
            $provider->register();
        }

        // Make sure we're not adding duplicates
        if (!in_array($provider, $this->serviceProviders, true)) {
            $this->serviceProviders[] = $provider;
        }

        return $provider;  // Return the provider for chaining
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

    public function detectEnviroment(Closure $callback = null)
    {

    }


    // Region: Environment Management
    /**
     * Get or check the current application environment.
     *
     * @param  string|array ...$environments
     * @return string|bool
     */
    public function environment(...$environments)
    {
        return env('APP_ENV') == $environments;
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

    protected function registerCoreContainerAliases(): void
    {
        $aliases = [
            'app' => [self::class, Container::class, Application::class],
            'events' => [\Rose\Events\Dispatcher::class, \Rose\Contracts\Events\Dispatcher::class],
            'encrypter' => [\Rose\Security\Encryption::class],
            'session' => [\Rose\Session\Manager\SessionManager::class],
        ];

        foreach ($aliases as $key => $targets) {
            foreach ($targets as $alias) {
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
    public function resolveProvider($provider)
    {
        return new $provider($this);
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
