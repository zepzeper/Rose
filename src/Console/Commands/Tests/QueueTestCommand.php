<?php

namespace Rose\Console\Commands\Tests;

use Rose\Console\BaseCommand;
use Symfony\Component\Console\Input\InputArgument;

class QueueTestCommand extends BaseCommand
{
    protected static string $defaultName = 'queue:test';

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName(self::$defaultName)
             ->setDescription('Test the queue implementation')
             ->addArgument('connection', InputArgument::OPTIONAL, 'The queue connection to test', 'file');
    }

    /**
     * Execute the command.
     *
     * @return int
     */
    protected function handle()
    {
        $io = $this->io();
        $io->title('Queue Implementation Test');
        
        $connection = $this->input->getArgument('connection');
        $io->section("Testing queue connection: {$connection}");
        
        // Check if queue manager is available
        if (!$this->app->bound('queue')) {
            $io->error('Queue manager not available. Make sure QueueServiceProvider is registered.');
            return 1;
        }
        
        try {
            // Get the queue manager
            $queueManager = $this->app->make('queue');
            
            // Create a test job class on the fly
            $jobName = 'QueueTestJob_' . time();
            $jobClass = $this->createTestJobClass($jobName);
            
            // Clear the queue
            $io->text('Clearing existing jobs from queue...');
            $queueManager->connection($connection)->clear();
            
            // Push a test job
            $io->text('Pushing test job to queue...');
            $jobData = [
                'test_id' => uniqid(),
                'timestamp' => time(),
                'message' => "Test job from command"
            ];
            
            $jobId = $queueManager->push($jobClass, $jobData, 'default', $connection);
            $io->success("Job pushed with ID: {$jobId}");
            
            // Check queue size
            $size = $queueManager->connection($connection)->size();
            $io->text("Queue size: {$size}");
            
            if ($size === 0) {
                $io->error('Job was not successfully pushed to the queue.');
                return 1;
            }
            
            // Retrieve and process job
            $io->text('Retrieving job from queue...');
            $job = $queueManager->connection($connection)->pop();
            
            if (!$job) {
                $io->error('Could not retrieve job from queue.');
                return 1;
            }
            
            $io->text("Processing job ID: " . $job->getId());
            
            // Get the QueueWorker
            $worker = $this->app->make('Rose\Queue\QueueWorker');
            $result = $worker->processJob($job);
            
            $io->success("Job processed " . ($result ? "successfully" : "with errors"));
            
            // Check queue size after processing
            $size = $queueManager->connection($connection)->size();
            $io->text("Queue size after processing: {$size}");
            
            // Check log file
            $logFile = storage_path("logs/queue-test.log");
            if (file_exists($logFile)) {
                $io->section("Log file output:");
                $logs = file_get_contents($logFile);
                $lines = explode(PHP_EOL, trim($logs));
                $lastLines = array_slice($lines, -5);
                foreach ($lastLines as $line) {
                    if (!empty($line)) $io->text("  > {$line}");
                }
            } else {
                $io->warning("Log file not found. Job may not have executed correctly.");
            }
            
            $io->newLine();
            $io->success("Queue test completed successfully!");
            
            return 0;
            
        } catch (\Exception $e) {
            $io->error("Error during test: " . $e->getMessage());
            $io->text($e->getTraceAsString());
            return 1;
        }
    }
    
    /**
     * Create a temporary test job class.
     *
     * @param string $className
     * @return string
     */
    protected function createTestJobClass(string $className): string
    {
        $fullClassName = "\\App\\Jobs\\{$className}";
        
        // Define a temporary job class
        eval("
        namespace App\\Jobs;
        
        class {$className}
        {
            protected \$data;
            
            public function __construct(\$data = [])
            {
                \$this->data = \$data;
            }
            
            public function handle()
            {
                // Write to log file
                \$logFile = storage_path('logs/queue-test.log');
                \$logDir = dirname(\$logFile);
                
                if (!\is_dir(\$logDir)) {
                    mkdir(\$logDir, 0755, true);
                }
                
                file_put_contents(
                    \$logFile,
                    date('[Y-m-d H:i:s]') . ' Processed job from command: ' . json_encode(\$this->data) . PHP_EOL,
                    FILE_APPEND
                );
                
                return true;
            }
        }
        ");
        
        $this->io()->text("Created temporary test job class: {$fullClassName}");
        
        return $fullClassName;
    }
}
