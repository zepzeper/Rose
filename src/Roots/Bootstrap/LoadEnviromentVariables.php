<?php

namespace Rose\Roots\Bootstrap;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidFileException;
use Rose\Roots\Application;
use Rose\Support\Env;

/**
 * The LoadEnvironmentVariables class is responsible for loading and configuring
 * environment variables from your application's .env file. Environment variables
 * are a critical part of modern application configuration, allowing you to:
 * 
 * 1. Keep sensitive credentials secure
 * 2. Change configuration between environments
 * 3. Follow the twelve-factor app methodology
 * 4. Maintain configuration consistency
 * 
 * This bootstrapper works with the PHP dotenv library to parse .env files
 * and make their values available to your application in a secure and 
 * consistent way.
 */
class LoadEnviromentVariables
{
    /**
     * Bootstrap the environment loading process.
     * 
     * This method orchestrates the environment variable loading process by:
     * 1. Checking if configuration is cached (skipping if it is)
     * 2. Loading the .env file safely
     * 3. Handling any invalid file exceptions
     * 4. Registering environment services in the container
     * 
     * The process is skipped when configuration is cached because environment
     * variables are already compiled into the cached configuration at that point.
     *
     * @param  Application $app The application instance to bootstrap
     * @return void
     */
    public function bootstrap(Application $app)
    {
        // Skip if configuration is cached to improve performance
        if ($app->configurationIsCached()) {
            return;
        }

        try {
            // Attempt to load the .env file safely
            $this->createDotEnv($app)->safeLoad();
        } catch (InvalidFileException $e) {
            // Handle any parsing errors in the .env file
            $this->logErrorDie($e);
        }

        // Register environment services in the container
        $this->registerEnvInContainer($app);
    }

    /**
     * Register environment-related services in the application container.
     * 
     * This method makes environment information available throughout your
     * application by registering three key services:
     * 
     * 1. 'env.vars' - Raw environment variables
     * 2. 'env.repository' - The Dotenv repository for advanced usage
     * 3. 'environment' - A service to check the current environment
     * 
     * These services provide different levels of access to environment
     * configuration, from raw values to sophisticated environment checking.
     *
     * @param Application $app The application instance
     */
    protected function registerEnvInContainer(Application $app)
    {
        // Make raw environment variables available
        $app->instance('env.vars', $_ENV);

        // Register the environment repository for advanced operations
        $app->instance('env.repository', Env::getRepository());

        // Provide an environment checker that defaults to 'production'
        $app->bind('environment', function () use ($app) {
            return $app->make('config')->get('app.env', 'production');
        });
    }

    /**
     * Create a new Dotenv instance for loading environment variables.
     * 
     * This method configures a Dotenv instance using your application's
     * environment settings. It uses:
     * 
     * 1. A custom environment repository
     * 2. The application's environment path (usually your app's root)
     * 3. The environment file name (typically .env)
     * 
     * The resulting Dotenv instance knows where to find and how to parse
     * your environment configuration.
     *
     * @param  Application $app The application instance
     * @return Dotenv The configured Dotenv instance
     */
    protected function createDotEnv(Application $app)
    {
        return Dotenv::create(
            Env::getRepository(),
            $app->environmentPath(),
            $app->environmentFile()
        );
    }

    /**
     * Handle invalid environment file exceptions.
     * 
     * When there's a problem parsing your .env file, this method:
     * 1. Could log the error (currently commented)
     * 2. Sets a 500 HTTP response code
     * 3. Terminates the application
     * 
     * This strict handling ensures configuration problems are caught early,
     * preventing the application from running with invalid settings.
     *
     * @param  InvalidFileException $e The caught exception
     * @return void
     */
    protected function logErrorDie(InvalidFileException $e)
    {
        // Here you could add logging before termination
        // Log::error('Invalid environment file: ' . $e->getMessage());
        
        http_response_code(500);
        exit(1);
    }
}
