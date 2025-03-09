<?php

namespace Rose\Console;

use Rose\Roots\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Application as SymfonyApplication;

abstract class BaseCommand extends Command
{
    /**
     * The Rose application instance.
     *
     * @var \Rose\Roots\Application
     */
    protected $app;

    /**
     * The input interface implementation.
     *
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $input;

    /**
     * The output interface implementation.
     *
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    /**
     * The console command name.
     *
     * @var string
     */
    protected static string $defaultName;

    /**
     * Create a new console command instance.
     *
     * @param \Rose\Roots\Application|null $app
     * @return void
     */
    public function __construct(?Application $app = null)
    {
        parent::__construct(static::$defaultName);

        $this->app = $app;
    }

    /**
     * Execute the console command.
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        return $this->handle() ?? Command::SUCCESS;
    }

    /**
     * Handle the command execution.
     *
     * @return int|null
     */
    abstract protected function handle();

    /**
     * Create a new SymfonyStyle instance.
     *
     * @return \Symfony\Component\Console\Style\SymfonyStyle
     */
    protected function io()
    {
        return new SymfonyStyle($this->input, $this->output);
    }

    /**
     * Set the Symfony application.
     * 
     * @param \Symfony\Component\Console\Application $application
     * @return void
     */
    public function setApplication(?SymfonyApplication $application = null): void
    {
        parent::setApplication($application);
        
        // If the application is our ConsoleApplication, extract the Rose app
        if ($application !== null && $application instanceof ConsoleApplication) {
            $this->app = $application->getRoseApp();
        }
    }
}
