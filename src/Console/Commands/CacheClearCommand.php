<?php

namespace Rose\Console\Commands;

use Rose\Console\BaseCommand;
use Symfony\Component\Console\Input\InputOption;

class CacheClearCommand extends BaseCommand
{
    protected static string $defaultName = 'cache:clear';

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName(self::$defaultName)
             ->setDescription('Clear the application cache')
             ->addOption('type', 't', InputOption::VALUE_OPTIONAL, 'Type of cache to clear (config, routes, views, or all)', 'all');
    }

    /**
     * Execute the command.
     *
     * @return int
     */
    protected function handle()
    {
        $type = $this->input->getOption('type');
        $io = $this->io();
        
        switch ($type) {
            case 'config':
                $this->clearConfigCache();
                $io->success('Configuration cache cleared!');
                break;
                
            case 'routes':
                $this->clearRoutesCache();
                $io->success('Routes cache cleared!');
                break;
                
            case 'views':
                $this->clearViewCache();
                $io->success('View cache cleared!');
                break;
                
            case 'all':
                $this->clearConfigCache();
                $this->clearRoutesCache();
                $this->clearViewCache();
                $this->clearAppCache();
                $io->success('All caches cleared successfully!');
                break;
                
            default:
                $io->error("Unknown cache type: $type");
                return 1;
        }
        
        return 0;
    }
    
    /**
     * Clear the configuration cache.
     *
     * @return void
     */
    protected function clearConfigCache()
    {
        $configCachePath = $this->app->cachedConfigPath();
        
        if (file_exists($configCachePath)) {
            @unlink($configCachePath);
        }
    }
    
    /**
     * Clear the routes cache.
     *
     * @return void
     */
    protected function clearRoutesCache()
    {
        $routesCachePath = $this->app->bootstrapPath('cache/routes.php');
        
        if (file_exists($routesCachePath)) {
            @unlink($routesCachePath);
        }
    }
    
    /**
     * Clear the view cache.
     *
     * @return void
     */
    protected function clearViewCache()
    {
        $viewCachePath = $this->app->storagePath('framework/views');
        
        if (is_dir($viewCachePath)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($viewCachePath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            
            foreach ($files as $file) {
                if ($file->isDir()) {
                    @rmdir($file->getRealPath());
                } else {
                    @unlink($file->getRealPath());
                }
            }
        }
    }
    
    /**
     * Clear the application cache.
     *
     * @return void
     */
    protected function clearAppCache()
    {
        $cachePath = $this->app->storagePath('framework/cache');
        
        if (is_dir($cachePath)) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($cachePath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            
            foreach ($files as $file) {
                if ($file->isDir()) {
                    @rmdir($file->getRealPath());
                } else {
                    @unlink($file->getRealPath());
                }
            }
        }
    }
}
