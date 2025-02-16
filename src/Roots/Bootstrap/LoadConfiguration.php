<?php

namespace Rose\Roots\Bootstrap;

use Rose\Roots\Application;
use Symfony\Component\Finder\Finder;

class LoadConfiguration
{

    protected function bootstrap(Application $app) {}

    protected function loadConfigurationFiles() {}

    protected function loadConfigurationFile() {}

    protected function getConfigurationFiles() {}

    protected function getNestedDir(){}

    protected function getBaseConfiguration()
    {
        $config = [];

        foreach (Finder::create()->files()->name("*.php")->in(__DIR__ . '/../../config') as $file)
        {
            $config[basename($file->getRealPath(), '.php')] = require $file->getRealPath();
        }

        return $config;
    }
}
