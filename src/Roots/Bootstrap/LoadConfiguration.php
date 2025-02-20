<?php

namespace Rose\Roots\Bootstrap;

use Rose\Config\Repository;
use Rose\Roots\Application;
use Symfony\Component\Finder\Finder;

/**
 * The LoadConfiguration class is responsible for bootstrapping your application's
 * configuration system. It handles loading configuration values from various sources
 * and makes them available throughout your application in a consistent way.
 * 
 * This class serves several important purposes:
 * 1. Loads configuration from PHP files in your config directory
 * 2. Supports configuration caching for performance
 * 3. Handles nested configuration directories
 * 4. Sets up crucial application defaults
 * 
 * The configuration system is a fundamental part of your application, providing
 * a centralized way to manage settings that might change between environments
 * or deployments.
 */
class LoadConfiguration
{
    /**
     * Bootstrap the configuration process for the application.
     * 
     * This method orchestrates the entire configuration loading process:
     * 1. Checks for cached configuration
     * 2. Creates the configuration repository
     * 3. Loads configuration files if needed
     * 4. Sets up application environment
     * 5. Configures global PHP settings
     * 
     * The process is optimized to use cached configuration when available,
     * significantly improving performance in production environments.
     *
     * @param  Application $app The application instance to configure
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $items = [];
        $cachedConfigLoaded = false;

        // Check for and load cached configuration if available
        if (file_exists($cached_config = $app->getCachedConfigPath())) {
            $items = include $cached_config;
            $cachedConfigLoaded = true;
            $app->instance('cached_config_loaded', $cachedConfigLoaded);
        }

        // Create and register the configuration repository
        $app->instance('config', $config = new Repository($items));

        // Load configuration files if not using cached config
        if (! $cachedConfigLoaded) {
            $this->loadConfigurationFiles($app, $config);
        }

        // Configure application environment and defaults
        $app->detectEnviroment(fn () => $config->get('app.env', 'production'));
        
        // Set global PHP configuration defaults
        date_default_timezone_set($config->get('app.timezone', 'UTC'));
        mb_internal_encoding('UTF-8');
    }

    /**
     * Load all configuration files into the repository.
     * 
     * This method handles loading individual configuration files and organizing
     * their contents in the repository. It maintains the directory structure
     * in the configuration hierarchy, allowing for better organization of
     * configuration files.
     *
     * @param Application $app  The application instance
     * @param Repository $repo  The configuration repository
     */
    protected function loadConfigurationFiles(Application $app, Repository $repo)
    {
        $config_files = $this->getConfigurationFiles($app);
        foreach ($config_files as $name => $path) {
            $this->loadConfigurationFile($name, $path, $repo);
        }
    }

    /**
     * Load a single configuration file into the repository.
     * 
     * This method safely loads a PHP configuration file and stores its contents
     * in the repository under the appropriate key. It uses a closure to ensure
     * the file is loaded in its own scope, preventing variable conflicts.
     *
     * @param string $name     The configuration key
     * @param string $path     Path to the configuration file
     * @param Repository $repo The configuration repository
     */
    protected function loadConfigurationFile($name, $path, Repository $repo)
    {
        // Load file in isolated scope using closure
        $config_content = (fn () => include $path)();
        $repo->set($name, $config_content);
    }

    /**
     * Discover and map all configuration files in the application.
     * 
     * This method scans the configuration directory for PHP files and creates
     * a mapping of configuration keys to file paths. It preserves the directory
     * structure in the configuration hierarchy, allowing for organized
     * configuration files.
     * 
     * For example:
     * config/
     *   app.php          -> 'app'
     *   database.php     -> 'database'
     *   services/
     *     cache.php      -> 'services.cache'
     *     queue.php      -> 'services.queue'
     *
     * @param  Application $app The application instance
     * @return array Mapping of configuration keys to file paths
     */
    protected function getConfigurationFiles(Application $app)
    {
        $files = [];
        $configPath = realpath($app->configPath());

        if (! $configPath) {
            return [];
        }

        // Find all PHP files in the config directory
        foreach (Finder::create()->files()->name("*.php")->in($configPath) as $file) {
            $nestedDir = $this->getNestedDir($file, $configPath);
            $files[$nestedDir.basename($file->getRealPath(), '.php')] = $file->getRealPath();
        }

        // Sort keys for consistent loading order
        ksort($files, SORT_NATURAL);
        return $files;
    }

    /**
     * Generate the nested directory path for configuration keys.
     * 
     * This method converts directory paths into dot notation for configuration
     * keys. This allows configuration files to be organized in subdirectories
     * while maintaining a logical hierarchy in the configuration array.
     * 
     * Example:
     * config/services/cache.php -> 'services.cache'
     *
     * @param \SplFileInfo $file The configuration file
     * @param string $path       The base configuration path
     * @return string           The nested directory in dot notation
     */
    protected function getNestedDir($file, $path)
    {
        $dir = $file->getPath();
        if ($nested = trim(str_replace($path, '', $dir), DIRECTORY_SEPARATOR)) {
            $nested = str_replace(DIRECTORY_SEPARATOR, '.', $nested).'.';
        }
        return $nested;
    }

    /**
     * Load the framework's base configuration files.
     * 
     * This method loads configuration files that come with the framework itself,
     * providing default settings that can be overridden by application-specific
     * configuration.
     *
     * @return array The framework's base configuration
     */
    protected function getBaseConfiguration()
    {
        $config = [];
        foreach (Finder::create()->files()->name("*.php")->in(__DIR__ . '/../../config') as $file) {
            $config[basename($file->getRealPath(), '.php')] = include $file->getRealPath();
        }
        return $config;
    }
}
