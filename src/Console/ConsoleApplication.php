<?php

namespace Rose\Console;

use Rose\Roots\Application;
use Symfony\Component\Console\Application as SymfonyConsole;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleApplication extends SymfonyConsole
{
    /**
     * The Rose application instance.
     *
     * @var \Rose\Roots\Application
     */
    protected $app;

    /**
     * Create a new Console Application instance.
     *
     * @param \Rose\Roots\Application $app
     * @param string $version
     * @return void
     */
    public function __construct(Application $app, $version = '0.1.alpha')
    {
        parent::__construct('Rose Framework', $version);
        
        $this->app = $app;
        
        // Register the base commands
        $this->registerBaseCommands();
    }

    /**
     * Register the base console commands for the application.
     *
     * @return void
     */
    protected function registerBaseCommands()
    {
        // Register the serve command
        $this->add(new ServeCommand());
    }

    /**
     * Register all of the commands in a directory.
     *
     * @param string $path
     * @return void
     */
    public function registerCommandsFromDirectory($path)
    {
        if (!is_dir($path)) {
            return;
        }

        $namespace = 'App\\Console\\Commands\\';
        $files = glob($path . '/*.php');
        
        foreach ($files as $file) {
            $className = $namespace . pathinfo($file, PATHINFO_FILENAME);
            
            if (class_exists($className)) {
                $command = new $className($this->app);
                $this->add($command);
            }
        }
    }
    
    /**
     * Get the Rose application instance.
     *
     * @return \Rose\Roots\Application
     */
    public function getLaravel()
    {
        return $this->app;
    }
    
    /**
     * Run the console application.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    public function run(?InputInterface $input = null, ?OutputInterface $output = null): int
    {
        // Bootstrap the application if it hasn't been bootstrapped
        if (!$this->app->hasBeenBootstrapped()) {
            $this->bootstrap();
        }
        
        return parent::run($input, $output);
    }
    
    /**
     * Bootstrap the application for console commands.
     *
     * @return void
     */
    protected function bootstrap()
    {
        $bootstrappers = [
            \Rose\Roots\Bootstrap\LoadEnviromentVariables::class,
            \Rose\Roots\Bootstrap\LoadConfiguration::class,
            \Rose\Roots\Bootstrap\RegisterProviders::class,
            \Rose\Roots\Bootstrap\BootProvider::class,
        ];
        
        $this->app->bootstrapWith($bootstrappers);
    }
}


