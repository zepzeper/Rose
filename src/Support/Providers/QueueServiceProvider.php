<?php

namespace Rose\Support\Providers;

use Rose\Console\Commands\WorkerCommand;
use Rose\Queue\QueueManager;
use Rose\Queue\QueueWorker;
use Rose\Support\ServiceProvider;

class QueueServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('queue', function ($app) {
            // Get the queue configuration
            $config = $app->make('config')->get('queue', [
                'default' => 'file',
                'connections' => [
                    'file' => [
                        'driver' => 'file',
                        'path' => storage_path('queue'),
                    ],
                    'redis' => [
                        'driver' => 'redis',
                        'host' => '127.0.0.1',
                        'port' => 6379,
                        'database' => 0,
                    ],
                ],
            ]);
            
            return new QueueManager($app, $config);
        });
        
        $this->app->singleton(QueueManager::class, function ($app) {
            return $app->make('queue');
        });
        
        $this->app->singleton(QueueWorker::class, function ($app) {
            return new QueueWorker($app->make(QueueManager::class), $app);
        });
    }
    
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->commands([
            WorkerCommand::class,
        ]);
        
        // Publish configuration file
        $this->publishes([
            __DIR__.'/../config/queue.php' => config_path('queue.php'),
        ], 'config');
    }
}
