<?php

namespace Rose\Console\Commands;

use Rose\Console\BaseCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class ServeCommand extends BaseCommand
{
    protected static string $defaultName = 'serve';

    protected function configure()
    {
        $this->setName(self::$defaultName)
            ->setDescription('Starts the PHP development server and Vite')
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'Host to serve on', '127.0.0.1')
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'Port to serve on', '8000')
            ->addOption('docroot', null, InputOption::VALUE_OPTIONAL, 'Document root', 'public')
            ->addOption('vite', null, InputOption::VALUE_NONE, 'Run Vite development server as well');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;
        
        return $this->handle();
    }

    protected function handle()
    {
        // Get options from the input
        $host = $this->input->getOption('host');
        $port = $this->input->getOption('port');
        $docroot = $this->input->getOption('docroot');
        $runVite = $this->input->getOption('vite');

        // Command to start PHP built-in server
        $phpCommand = sprintf(
            'php -S %s:%s -t %s',
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($docroot)
        );

        $this->output->writeln("<info>Starting PHP server at http://$host:$port</info>");
        $this->output->writeln("<comment>Press Ctrl+C to stop the server</comment>");

        // Start PHP server in the background
        $phpProcess = proc_open($phpCommand, [], $pipes);

        if ($runVite) {
            $this->output->writeln("<info>Starting Vite development server...</info>");
            // Command to run Vite (Ensure it's in the right directory)
            $viteCommand = 'npm run dev';

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows specific command (start without opening a new window)
                $viteCommand = 'start /B ' . $viteCommand;
            }

            proc_open($viteCommand, [], $pipes);
        }

        // Keep PHP process alive
        proc_close($phpProcess);

        return Command::SUCCESS;
    }
}
