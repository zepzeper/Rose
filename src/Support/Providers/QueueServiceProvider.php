<?php

namespace Rose\Support\Providers;

use Rose\Support\ServiceProvider;
use Rose\Queue\QueueManager;
use Rose\Queue\QueueWorker;

class QueueServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerQueueManager();
        $this->registerQueueWorker();
    }

    /**
     * Register the queue manager.
     *
     * @return void
     */
    protected function registerQueueManager()
    {
        $this->app->singleton('queue', function ($app) {
            // Get queue configuration from config
            $config = $app['config']['queue'] ?? [];
            
            // Create the manager instance
            return new QueueManager($app, $config);
        });
    }

    /**
     * Register the queue worker.
     *
     * @return void
     */
    protected function registerQueueWorker()
    {
        $this->app->singleton(QueueWorker::class, function ($app) {
            return new QueueWorker(
                $app->make('queue'),
                $app
            );
        });
    }
}
