<?php

namespace Rose\Console\Commands;

use Rose\Console\BaseCommand;
use Symfony\Component\Console\Input\InputOption;

class WorkerCommand extends BaseCommand
{
    protected static string $defaultName = 'worker:start';

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName(self::$defaultName)
             ->setDescription('Start the queue worker')
             ->addOption('queue', null, InputOption::VALUE_OPTIONAL, 'The queue to work on', 'default')
             ->addOption('daemon', 'd', InputOption::VALUE_NONE, 'Run in daemon mode')
             ->addOption('sleep', null, InputOption::VALUE_OPTIONAL, 'Number of seconds to sleep when no job is available', 3)
             ->addOption('tries', null, InputOption::VALUE_OPTIONAL, 'Number of times to attempt a job before logging it failed', 3);
    }

    /**
     * Execute the command.
     *
     * @return int
     */
    protected function handle()
    {
        $queue = $this->input->getOption('queue');
        $daemon = $this->input->getOption('daemon');
        $sleep = $this->input->getOption('sleep');
        $tries = $this->input->getOption('tries');
        
        $io = $this->io();
        $io->title('Starting Queue Worker');
        
        // Output the worker configuration
        $io->table(
            ['Option', 'Value'],
            [
                ['Queue', $queue],
                ['Daemon', $daemon ? 'Yes' : 'No'],
                ['Sleep', $sleep],
                ['Tries', $tries],
            ]
        );
        
        // If we have a queue manager in the application, we can use it here
        if ($this->app->bound('queue')) {
            $queueManager = $this->app->make('queue');
            
            // Here you would start the worker via the queue manager
            // This is just a placeholder example
            $io->section('Processing Jobs');
            
            if ($daemon) {
                $this->runDaemon($queue, $sleep, $tries);
            } else {
                $this->runOnce($queue);
            }
            
            return 0;
        }
        
        $io->error('Queue manager not found in the application.');
        return 1;
    }
    
    /**
     * Run the worker daemon process.
     *
     * @param string $queue
     * @param int $sleep
     * @param int $tries
     * @return void
     */
    protected function runDaemon($queue, $sleep, $tries)
    {
        $io = $this->io();
        $io->writeln("<info>Worker started in daemon mode. Press Ctrl+C to stop.</info>");
        
        // Example daemon loop
        while (true) {
            $this->processJob($queue, $tries);
            
            // Sleep when no jobs
            sleep($sleep);
            
            // Check for shutdown signal (this would need to be implemented)
            if ($this->shouldShutdown()) {
                $io->writeln("<comment>Shutdown signal received. Stopping worker...</comment>");
                break;
            }
        }
    }
    
    /**
     * Run the worker for a single iteration.
     *
     * @param string $queue
     * @return void
     */
    protected function runOnce($queue)
    {
        $io = $this->io();
        $io->writeln("<info>Processing jobs from queue: $queue</info>");
        
        // Process a single job
        $processed = $this->processJob($queue);
        
        if ($processed) {
            $io->success('Job processed successfully.');
        } else {
            $io->note('No jobs found in queue.');
        }
    }
    
    /**
     * Process a job from the queue.
     *
     * @param string $queue
     * @param int $tries
     * @return bool
     */
    protected function processJob($queue, $tries = 3)
    {
        // Placeholder for actual job processing logic
        // This would connect to your queue system
        
        // Simulate job processing for example
        $this->output->write('.');
        
        // Return true if a job was processed, false otherwise
        return false;
    }
    
    /**
     * Determine if the worker should shutdown.
     *
     * @return bool
     */
    protected function shouldShutdown()
    {
        // Implement shutdown logic here
        // This might check for a file or signal to stop the worker
        return false;
    }
}
