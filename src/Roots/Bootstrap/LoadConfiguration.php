<?php

namespace Rose\Roots\Bootstrap;

use Rose\Config\Repository;
use Rose\Roots\Application;
use Symfony\Component\Finder\Finder;

class LoadConfiguration
{
    /**
     *
     * @param  Application $app
     * @return void
     */
    public function bootstrap(Application $app)
    {

        $items = [];

        $cachedConfigLoaded = false;

        // Cache check
        if (file_exists($cached_config = $app->getCachedConfigPath())) {
            $items = include $cached_config;

            $cachedConfigLoaded = !$cachedConfigLoaded;

            $app->instance('cached_config_loaded', $cachedConfigLoaded);
        }

        $app->instance('config', $config = new Repository($items));


        if (! $cachedConfigLoaded) {
            $this->loadConfigurationFiles($app, $config);
        }

        $app->detectEnviroment(fn () => $config->get('app.env', 'production'));

        // Default values...
        date_default_timezone_set($config->get('app.timezone', 'UTC'));
        mb_internal_encoding('UTF-8');

    }

    protected function loadConfigurationFiles(Application $app, Repository $repo)
    {

        $config_files = $this->getConfigurationFiles($app);

        foreach ($config_files as $name => $path) {
            $this->loadConfigurationFile($name, $path, $repo);
        }

    }

    protected function loadConfigurationFile($name, $path, Repository $repo)
    {
        $config_content = (fn () => include $path)();

        $repo->set($name, $config_content);
    }

    /**
     *
     * @param  Application $app
     * @return array;
     */
    protected function getConfigurationFiles(Application $app)
    {

        $files = [];

        $configPath = realpath($app->configPath());

        if (! $configPath) {
            return [];
        }

        foreach (Finder::create()->files()->name("*.php")->in($configPath) as $file) {
            $nestedDir = $this->getNestedDir($file, $configPath);

            $files[$nestedDir.basename($file->getRealPath(), '.php')] = $file->getRealPath();
        }

        ksort($files, SORT_NATURAL);

        return $files;
    }

    protected function getNestedDir($file, $path)
    {
        $dir = $file->getPath();

        if ($nested = trim(str_replace($path, '', $dir), DIRECTORY_SEPARATOR)) {
            $nested = str_replace(DIRECTORY_SEPARATOR, '.', $nested).'.';
        }

        return $nested;
    }

    protected function getBaseConfiguration()
    {
        $config = [];

        foreach (Finder::create()->files()->name("*.php")->in(__DIR__ . '/../../config') as $file) {
            $config[basename($file->getRealPath(), '.php')] = include $file->getRealPath();
        }

        return $config;
    }
}
