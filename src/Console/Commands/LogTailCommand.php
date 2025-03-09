<?php

namespace Rose\Console\Commands;

use Rose\Console\BaseCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class LogTailCommand extends BaseCommand
{
    protected static string $defaultName = 'log:tail';

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName(self::$defaultName)
             ->setDescription('Tail the application logs')
             ->addArgument('file', InputArgument::OPTIONAL, 'The log file to tail (without extension)', 'laravel')
             ->addOption('lines', 'l', InputOption::VALUE_OPTIONAL, 'The number of lines to tail', 20)
             ->addOption('follow', 'f', InputOption::VALUE_NONE, 'Continue to follow the logs');
    }

    /**
     * Execute the command.
     *
     * @return int
     */
    protected function handle()
    {
        $file = $this->input->getArgument('file');
        $lines = (int) $this->input->getOption('lines');
        $follow = $this->input->getOption('follow');
        
        $io = $this->io();
        
        // Build the path to the log file
        $logPath = $this->getLogPath($file);
        
        if (!file_exists($logPath)) {
            $io->error("Log file does not exist: $logPath");
            return 1;
        }
        
        // Display initial lines
        $this->displayLines($logPath, $lines);
        
        // If follow option is enabled, continue to display new lines
        if ($follow) {
            $io->writeln("\n<info>Following log file... Press Ctrl+C to stop.</info>\n");
            $this->followLog($logPath);
        }
        
        return 0;
    }
    
    /**
     * Get the path to the log file.
     *
     * @param string $file
     * @return string
     */
    protected function getLogPath($file)
    {
        // Check if the file already has an extension
        if (pathinfo($file, PATHINFO_EXTENSION) === '') {
            $file .= '.log';
        }
        
        return $this->app->storagePath("logs/{$file}");
    }
    
    /**
     * Display the specified number of lines from the log file.
     *
     * @param string $logPath
     * @param int $lines
     * @return void
     */
    protected function displayLines($logPath, $lines)
    {
        $this->output->writeln($this->tailFile($logPath, $lines));
    }
    
    /**
     * Extract the last N lines from a file.
     *
     * @param string $filePath
     * @param int $lines
     * @return string
     */
    protected function tailFile($filePath, $lines)
    {
        $result = '';
        
        // Use the system tail command if available (Unix/Linux/macOS)
        if (function_exists('exec') && strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            $output = [];
            exec("tail -n {$lines} " . escapeshellarg($filePath), $output);
            $result = implode("\n", $output);
        } else {
            // Manual tail implementation for Windows or if exec is disabled
            $file = new \SplFileObject($filePath, 'r');
            $file->seek(PHP_INT_MAX); // Seek to end of file
            $totalLines = $file->key(); // Get total lines
            
            $linesToRead = min($lines, $totalLines);
            $lineOffset = max(0, $totalLines - $linesToRead);
            
            $result = [];
            $file->seek($lineOffset);
            
            while (!$file->eof()) {
                $result[] = $file->fgets();
            }
            
            $result = implode('', $result);
        }
        
        return $result;
    }
    
    /**
     * Follow the log file for new entries.
     *
     * @param string $logPath
     * @return void
     */
    protected function followLog($logPath)
    {
        // Get the current size of the file
        $size = filesize($logPath);
        
        while (true) {
            clearstatcache(true, $logPath);
            $currentSize = filesize($logPath);
            
            if ($currentSize > $size) {
                // File has grown, display the new content
                $file = fopen($logPath, 'r');
                fseek($file, $size);
                
                $newContent = fread($file, $currentSize - $size);
                $this->output->write($newContent);
                
                fclose($file);
                $size = $currentSize;
            }
            
            // Wait a bit before checking again
            usleep(500000); // 0.5 seconds
        }
    }
}
