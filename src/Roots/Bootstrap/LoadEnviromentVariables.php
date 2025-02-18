<?php

namespace Rose\Roots\Bootstrap;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidFileException;
use Rose\Roots\Application;
use Rose\Support\Env;

class LoadEnviromentVariables
{
    /**
    * @param Application $app
    * @return void
    */
    public function bootstrap(Application $app)
    {
        if ($app->configurationIsCached())
        {
            return;
        }

        try {
            $this->createDotEnv($app)->safeLoad();
        } catch (InvalidFileException $e)
        {
            $this->logErrorDie($e);
        }
    }

    protected function createDotEnv(Application $app)
    {
        return Dotenv::create(Env::getRepository(), $app->environmentPath(), $app->environmentFile());
    }

    protected function logErrorDie(InvalidFileException $e)
    {

        // Do some logging

        http_response_code(500);

        exit(1);
    }
}
