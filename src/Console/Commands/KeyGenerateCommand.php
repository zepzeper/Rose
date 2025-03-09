<?php

namespace Rose\Console\Commands;

use Rose\Console\BaseCommand;
use Symfony\Component\Console\Input\InputOption;

class KeyGenerateCommand extends BaseCommand
{
    protected static string $defaultName = 'key:generate';

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName(self::$defaultName)
             ->setDescription('Generate an application encryption key')
             ->addOption('show', null, InputOption::VALUE_NONE, 'Display the key instead of modifying files')
             ->addOption('force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production');
    }

    /**
     * Execute the command.
     *
     * @return int
     */
    protected function handle()
    {
        $key = $this->generateRandomKey();
        
        if ($this->input->getOption('show')) {
            $this->output->writeln("<comment>$key</comment>");
            return 0;
        }
        
        // Check if application is in production
        if ($this->app->isProduction() && !$this->input->getOption('force')) {
            $this->output->writeln('<error>Application In Production!</error>');
            $this->output->writeln('Use the --force option to force the operation.');
            return 1;
        }
        
        // Update the .env file
        if (!$this->setKeyInEnvironmentFile($key)) {
            $this->output->writeln('<error>Unable to set application key. Check file permissions.</error>');
            return 1;
        }
        
        $this->output->writeln('<info>Application key set successfully.</info>');
        
        return 0;
    }
    
    /**
     * Generate a random key for the application.
     *
     * @return string
     */
    protected function generateRandomKey()
    {
        return 'base64:' . base64_encode(random_bytes(32));
    }
    
    /**
     * Set the application key in the environment file.
     *
     * @param string $key
     * @return bool
     */
    protected function setKeyInEnvironmentFile($key)
    {
        $envFile = $this->app->environmentFile();
        $envPath = $this->app->environmentPath() . '/' . $envFile;
        
        if (!file_exists($envPath)) {
            // If .env doesn't exist, create one with the key
            return file_put_contents($envPath, "APP_KEY=$key\n") !== false;
        }
        
        // Replace the existing APP_KEY or add it if it doesn't exist
        $content = file_get_contents($envPath);
        
        if (preg_match('/^APP_KEY=(.*)$/m', $content)) {
            // Replace existing key
            $content = preg_replace('/^APP_KEY=(.*)$/m', "APP_KEY=$key", $content);
        } else {
            // Add key if it doesn't exist
            $content .= "\nAPP_KEY=$key\n";
        }
        
        return file_put_contents($envPath, $content) !== false;
    }
}
