<?php

namespace Rose\Support\Providers;

use Rose\Concurrency\Pool;
use Rose\Concurrency\ProcessManager;
use Rose\Support\ServiceProvider;

class ConcurrencyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ProcessManager::class, function ($app) {
            return new ProcessManager($app);
        });

        $this->app->bind(Pool::class, function($parameters) {
            $concurrency = $parameters['concurrency'] ?? null;
            $runtime = $parameters['runtime'] ?? null;

            if ($concurrency && $runtime)
            {
                return new Pool($concurrency, $runtime);
            } else if ($concurrency)
            {
                return new Pool($concurrency);
            }

            return new Pool();
        });

        $this->app->alias(ProcessManager::class, 'concurrency');
    }

    public function boot(): void
    {
        $configPath = __DIR__ . '/../../config/concurrency.php';

        if (true) {
            $this->publishes([
                $configPath => $this->app->configPath('concurrency.php'),
            ], 'concurrency-config');
        }

        $this->mergeConfigFrom($configPath, 'concurrency');
    }
}
