<?php

namespace Rose\Console;

use Rose\Support\ServiceProvider;

class ConsoleServiceProvider extends ServiceProvider
{
    /**
     * All of the console commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        Commands\CacheClearCommand::class,
        Commands\WorkerCommand::class,
        Commands\LogTailCommand::class,
        Commands\RouteListCommand::class,
        Commands\KeyGenerateCommand::class,
        Commands\ServeCommand::class,
        Commands\Tests\QueueTestCommand::class,
    ];

    /**
     * Register any console services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('console', function ($app) {
            return new ConsoleApplication($app, $app->version());
        });

        $this->commands($this->commands);
    }

    /**
     * Register the given commands with the console application.
     *
     * @param array $commands
     * @return void
     */
    protected function commands(array $commands)
    {
        // Register each command with the application
        $console = $this->app->make('console');

        foreach ($commands as $command) {
            $console->add(new $command($this->app));
        }
    }

    /**
     * Register all of the available commands in a specified directory.
     *
     * @param string $path
     * @return void
     */
    public function registerCommandsFromDirectory($path)
    {
        $console = $this->app->make('console');
        $console->registerCommandsFromDirectory($path);
    }
}
