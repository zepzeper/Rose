<?php

namespace Rose\Console;

use Rose\Roots\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class BaseCommand extends Command
{
    protected Application $app;

    protected InputInterface $input;
    protected OutputInterface $output;

    protected static string $defaultName;

    /**
     * Create a new console command instance.
     *
     * @param Application|null $app
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
     * @param InputInterface $input
     * @param OutputInterface $output
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
     * @return SymfonyStyle
     */
    protected function io()
    {
        return new SymfonyStyle($this->input, $this->output);
    }

    /**
     * Set the application instance.
     *
     * @param Application $app
     * @return void
     */
    public function setApplication($app): void
    {
        $this->app = $app;
    }
}
