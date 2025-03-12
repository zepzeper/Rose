<?php

namespace Rose\Console\Commands;

use Rose\Console\BaseCommand;
use Rose\Queue\QueueWorker;
use Symfony\Component\Console\Helper\ProgressBar;
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
             ->addOption('connection', 'c', InputOption::VALUE_OPTIONAL, 'The queue connection to use', null)
             ->addOption('daemon', 'd', InputOption::VALUE_NONE, 'Run in daemon mode')
             ->addOption('sleep', null, InputOption::VALUE_OPTIONAL, 'Number of seconds to sleep when no job is available', 3)
             ->addOption('tries', null, InputOption::VALUE_OPTIONAL, 'Number of times to attempt a job before logging it failed', 3)
             ->addOption('max-jobs', 'm', InputOption::VALUE_OPTIONAL, 'The maximum number of jobs to process before stopping', 0)
             ->addOption('concurrency', null, InputOption::VALUE_OPTIONAL, 'Number of jobs to process simultaneously (in daemon mode)', 1);
    }

    /**
     * Execute the command.
     *
     * @return int
     */
    protected function handle()
    {
        $queue = $this->input->getOption('queue');
        $connection = $this->input->getOption('connection');
        $daemon = $this->input->getOption('daemon');
        $sleep = (int) $this->input->getOption('sleep');
        $tries = (int) $this->input->getOption('tries');
        $maxJobs = (int) $this->input->getOption('max-jobs');
        $concurrency = (int) $this->input->getOption('concurrency');
        
        $io = $this->io();
        $io->title('Rose Queue Worker');

        // Check if queue manager is available
        if (!$this->app->bound('queue')) {
            $io->error('Queue manager not available. Make sure QueueServiceProvider is registered.');
            return 1;
        }
        
        // Get the queue manager
        $queueManager = $this->app->make('queue');

        $connection = $this->app->make('config')->get('queue.default');
        
        // Output the worker configuration
        $io->table(
            ['Option', 'Value'],
            [
                ['Connection', $connection . ' (from config)'],
                ['Queue', $queue],
                ['Daemon Mode', $daemon ? 'Yes' : 'No'],
                ['Sleep Duration', $sleep . 's'],
                ['Max Tries', $tries],
                ['Max Jobs', $maxJobs > 0 ? $maxJobs : 'Unlimited'],
                ['Concurrency', $concurrency],
            ]
        );
        
        // Get the queue worker instance
        $worker = $this->app->make(QueueWorker::class);
        
        // Handle shutdown signals
        $this->listenForSignals($worker);
        
        try {
            $io->section('Starting Queue Worker');
            
            // Process jobs based on daemon mode
            if ($daemon) {
                $this->runWorkerDaemon($worker, $connection, $queue, $sleep, $tries, $concurrency);
            } else {
                $this->runWorkerOnce($worker, $queueManager, $connection, $queue, $maxJobs, $tries);
            }
            
            return 0;
        } catch (\Throwable $e) {
            $io->error('Queue worker failed: ' . $e->getMessage());
            $io->text($e->getTraceAsString());
            
            return 1;
        }
    }
    
    /**
     * Run the worker in daemon mode.
     *
     * @param QueueWorker $worker
     * @param string $connection
     * @param string $queue
     * @param int $sleep
     * @param int $tries
     * @param int $concurrency
     * @return void
     */
    protected function runWorkerDaemon(
        QueueWorker $worker,
        string $connection,
        string $queue,
        int $sleep,
        int $tries,
        int $concurrency
    ): void {
        $io = $this->io();
        $io->writeln(sprintf(
            '<info>Worker started in daemon mode. Processing jobs from queue [%s].</info>',
            $queue
        ));
        $io->writeln('<comment>Press Ctrl+C to stop the worker.</comment>');
        
        // Run the worker in daemon mode
        $worker->daemon($connection, $queue, $sleep, $tries, $concurrency);
    }
    
    /**
     * Run the worker for a single batch of jobs.
     *
     * @param QueueWorker $worker
     * @param object $queueManager
     * @param string|null $connection
     * @param string $queue
     * @param int $maxJobs
     * @param int $tries
     * @return void
     */
    protected function runWorkerOnce(
        QueueWorker $worker,
        object $queueManager,
        ?string $connection,
        string $queue,
        int $maxJobs,
        int $tries
    ): void {
        $io = $this->io();
        
        // Determine how many jobs to process
        $count = $maxJobs > 0 ? $maxJobs : $queueManager->size($queue, $connection);
        
        if ($count === 0) {
            $io->warning('No jobs available in queue: ' . $queue);
            return;
        }
        
        $io->writeln(sprintf(
            '<info>Processing %s job%s from queue [%s]...</info>',
            $maxJobs > 0 ? "up to {$maxJobs}" : $count,
            $count === 1 ? '' : 's',
            $queue
        ));
        
        // Create a progress bar
        $progressBar = new ProgressBar($this->output, $count);
        $progressBar->setFormat(
            ' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s% %memory:6s% | %message%'
        );
        $progressBar->setMessage('Starting...');
        $progressBar->start();
        
        // Process each job
        $processed = 0;
        $failures = 0;
        
        while (($maxJobs <= 0 || $processed < $maxJobs) && 
               ($job = $queueManager->connection($connection)->pop($queue))) {
            
            $progressBar->setMessage('Processing job: ' . $job->getId());
            
            try {
                $worker->processJob($job, $tries);
                $processed++;
            } catch (\Throwable $e) {
                $failures++;
                $progressBar->setMessage('<error>Failed: ' . $e->getMessage() . '</error>');
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $io->newLine(2);
        
        // Show results
        if ($processed > 0) {
            $io->success(sprintf(
                'Processed %d job%s with %d failure%s.',
                $processed,
                $processed === 1 ? '' : 's',
                $failures,
                $failures === 1 ? '' : 's'
            ));
        } else {
            $io->warning('No jobs were processed.');
        }
    }
    
    /**
     * Set up signal handlers for graceful shutdown.
     *
     * @param QueueWorker $worker
     * @return void
     */
    protected function listenForSignals(QueueWorker $worker): void
    {
        if (extension_loaded('pcntl')) {
            pcntl_async_signals(true);
            
            // Handle SIGTERM and SIGINT signals
            pcntl_signal(SIGTERM, function () use($worker) {
                $this->io()->warning('SIGTERM received, shutting down gracefully...');
                $worker->stop();
                $worker->logHealthMetrics(true);
                exit(0);
            });
            
            pcntl_signal(SIGINT, function () use ($worker) {
                $this->io()->warning('SIGINT received, shutting down gracefully...');
                $worker->stop();
                $worker->logHealthMetrics(true);
                exit(0);
            });
        }
    }
    
    /**
     * Helper function to get the storage path.
     *
     * @param string $path
     * @return string
     */
    protected function storage_path(string $path = ''): string
    {
        return $this->app->make('path.storage') . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}
