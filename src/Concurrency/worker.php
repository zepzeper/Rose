<?php

/**
 * Worker script for executing serialized closures in separate processes.
 * This script reads a serialized closure from stdin and executes it.
 */

// Make sure we're in a separate process
if (php_sapi_name() !== 'cli') {
    exit(1);
}

// Set error handling
error_reporting(E_ALL);
ini_set('display_errors', 'stderr');

// Function to handle errors
function handleError($errno, $errstr, $errfile, $errline) {
    fwrite(STDERR, json_encode([
        'error' => [
            'type' => $errno,
            'message' => $errstr,
            'file' => $errfile,
            'line' => $errline
        ]
    ]) . PHP_EOL);
    
    exit(1);
}

// Function to handle exceptions
function handleException($exception) {
    fwrite(STDERR, json_encode([
        'error' => [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]
    ]) . PHP_EOL);
    
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

foreach ($bootstrapFiles as $file) {
    if (file_exists($file)) {
        require_once $file;
        break;
    }
}

// Read serialized closure from stdin
$input = '';
while ($line = fgets(STDIN)) {
    $input .= $line;
    
    // Check if we have a complete serialized object
    try {
        $serializedClosure = unserialize($input);
        
        // If unserialization was successful, execute the closure
        if ($serializedClosure instanceof \YourFramework\Concurrency\Support\SerializableClosure) {
            break;
        }
    } catch (\Exception $e) {
        // Not complete yet, continue reading
    }
}

try {
    // Execute the closure
    $result = $serializedClosure();
    
    // Return the result
    echo serialize([
        'success' => true,
        'result' => $result
    ]);
    
    exit(0);
} catch (\Throwable $e) {
    // Return the error
    echo serialize([
        'success' => false,
        'error' => [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ]);
    
    exit(1);
}
