<?php

/**
 * Improved worker script for executing serialized closures in separate processes.
 * This script reads a serialized closure from stdin and executes it.
 */

// Make sure we're in a separate process
if (php_sapi_name() !== 'cli') {
    exit(1);
}

// Set error handling
error_reporting(E_ALL);
ini_set('display_errors', 'stderr');

// Custom error logging function that writes to a file for debugging
function log_debug($message) {
    $logDir = sys_get_temp_dir();
    file_put_contents(
        $logDir . '/rose_worker_' . getmypid() . '.log',
        date('[Y-m-d H:i:s] ') . $message . PHP_EOL,
        FILE_APPEND
    );
}

// Function to handle errors
function handleError($errno, $errstr, $errfile, $errline) {
    log_debug("ERROR: {$errstr} in {$errfile}:{$errline}");
    
    $errorOutput = [
        'success' => false,
        'error' => [
            'type' => $errno,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline
        ]
    ];
    
    echo serialize($errorOutput);
    exit(1);
}

// Function to handle exceptions
function handleException($exception) {
    log_debug("EXCEPTION: {$exception->getMessage()} in {$exception->getFile()}:{$exception->getLine()}");
    
    $errorOutput = [
        'success' => false,
        'error' => [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]
    ];
    
    echo serialize($errorOutput);
    exit(1);
}

// Register error and exception handlers
set_error_handler('handleError');
set_exception_handler('handleException');

// Try to bootstrap the application
$bootstrapFiles = [
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
    __DIR__ . '/../../../../vendor/autoload.php',
    __DIR__ . '/../../../../../vendor/autoload.php',
];

$loaded = false;
foreach ($bootstrapFiles as $file) {
    if (file_exists($file)) {
        require_once $file;
        $loaded = true;
        log_debug("Loaded autoloader: {$file}");
        break;
    }
}

if (!$loaded) {
    log_debug("Failed to load autoloader");
    echo serialize([
        'success' => false,
        'error' => [
            'message' => 'Failed to load autoloader',
            'code' => 1
        ]
    ]);
    exit(1);
}

log_debug("Starting worker process");

// Read serialized closure from stdin
$input = '';
while (!feof(STDIN)) {
    $input .= fread(STDIN, 1024);
}

log_debug("Read input: " . substr($input, 0, 100) . (strlen($input) > 100 ? '...' : ''));

// Ensure we have input
if (empty($input)) {
    log_debug("No input received");
    echo serialize([
        'success' => false,
        'error' => [
            'message' => 'No input received',
            'code' => 1
        ]
    ]);
    exit(1);
}

try {
    // Try to unserialize the input
    $serializedClosure = @unserialize($input);
    
    if ($serializedClosure === false) {
        throw new Exception('Failed to unserialize closure: ' . substr($input, 0, 100));
    }
    
    log_debug("Unserialized input of type: " . get_class($serializedClosure));
    
    // Make sure it's the right type of object
    if (!$serializedClosure instanceof \Rose\Support\SerializableClosure) {
        throw new Exception('Input is not a SerializableClosure: ' . get_class($serializedClosure));
    }
    
    // Execute the closure
    log_debug("Executing closure");
    $result = $serializedClosure();
    log_debug("Execution result: " . (is_scalar($result) ? $result : json_encode($result)));
    
    // Return the result
    $output = [
        'success' => true,
        'result' => $result
    ];
    
    log_debug("Sending output: " . json_encode($output));
    echo serialize($output);
    
    log_debug("Worker completed successfully");
    exit(0);
} catch (\Throwable $e) {
    log_debug("FATAL ERROR: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}");
    
    // Return the error
    $errorOutput = [
        'success' => false,
        'error' => [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ];
    
    echo serialize($errorOutput);
    exit(1);
}
