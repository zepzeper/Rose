<?php

namespace Rose\Roots\Bootstrap;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidFileException;
use Rose\Roots\Application;
use Rose\Support\Env;

class LoadEnviromentVariables
{
    /**
     * @param  Application $app
     * @return void
     */
    public function bootstrap(Application $app)
    {
        if ($app->configurationIsCached()) {
            return;
        }

        try {
            $this->createDotEnv($app)->safeLoad();
        } catch (InvalidFileException $e)
        {
            $this->logErrorDie($e);
        }

        $this->registerEnvInContainer($app);
    }

    protected function registerEnvInContainer(Application $app)
    {
        // Store raw environment variables
        $app->instance('env.vars', $_ENV);
        
        // Store Dotenv repository for advanced usage
        $app->instance('env.repository', Env::getRepository());
        
        // Bind environment checker
        $app->bind('environment', function() use ($app) {
            return $app->make('config')->get('app.env', 'production');
        });
    }

    /**
     * @param  Application $app
     * @return Dotenv
     */
    protected function createDotEnv(Application $app)
    {
        return Dotenv::create(Env::getRepository(), $app->environmentPath(), $app->environmentFile());
    }

    /**
     * @param  InvalidFileException $e
     * @return void
     */
    protected function logErrorDie(InvalidFileException $e)
    {

        // Do some logging

        http_response_code(500);

        exit(1);
    }
}
