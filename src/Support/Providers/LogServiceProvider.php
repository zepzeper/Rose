<?php

namespace Rose\Support\Providers;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Rose\Support\ServiceProvider;

class LogServiceProvider extends ServiceProvider
{
    /**
     * Register the logging services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('logger', function () {
            return $this->createLogger();
        });

        $this->app->bind(LoggerInterface::class, function ($app) {
            return $app->make('logger');
        });
    }

    /**
     * Create a configured Monolog instance.
     *
     * @return \Monolog\Logger
     */
    protected function createLogger()
    {
        $logger = new Logger('Rose');
        
        $this->configureHandlers($logger);
        
        return $logger;
    }

    /**
     * Configure the Monolog handlers.
     *
     * @param \Monolog\Logger $logger
     * @return void
     */
    protected function configureHandlers(Logger $logger)
    {
        $config = $this->app->make('config')->get('logger');

        $storageDir = $this->app->storagePath('logs');
        
        // Default log file path
        $logPath = $storageDir . '/rose.log';
        
        // Ensure the storage directory exists
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0755, true);
        }
        
        // Determine log level from config or use INFO as default
        $logLevel = $config['level'] ?? Level::Info;
        
        // Determine whether to use daily logs from config or use true as default
        $daily = $config['daily'] ?? true;
        
        // Set log format
        $dateFormat = "Y-m-d H:i:s";
        $output = "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
        $formatter = new LineFormatter($output, $dateFormat, true, true);
        
        // Create the appropriate handler
        if ($daily) {
            $maxFiles = $config['max_files'] ?? 7;
            $handler = new RotatingFileHandler($logPath, $maxFiles, $logLevel);
        } else {
            $handler = new StreamHandler($logPath, $logLevel);
        }
        
        $handler->setFormatter($formatter);
        $logger->pushHandler($handler);
        
        // Add console handler if specified in config
        if (($config['console'] ?? false) && php_sapi_name() === 'cli') {
            $consoleHandler = new StreamHandler('php://stdout', $logLevel);
            $consoleFormatter = new LineFormatter(
                "[%datetime%] %level_name%: %message%\n",
                $dateFormat,
                true,
                true
            );
            $consoleHandler->setFormatter($consoleFormatter);
            $logger->pushHandler($consoleHandler);
        }
    }
}
