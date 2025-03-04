<?php

// This is a simple script to test CORS middleware functionality

// Include autoloader
require_once __DIR__ . '/vendor/autoload.php';

use Rose\Http\Middleware\CorsMiddleware as RoseCorsMiddleware;
use Rose\Pipeline\Pipeline as RosePipeline;
use Rose\Roots\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Create a simple application container mock for testing
$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting()
    ->create();

// Function to test CORS with different configurations
function testCors($app, $title, $config, $origin = null, $method = 'GET')
{
    echo "\033[1;33m=== Testing: {$title} ===\033[0m\n";
    
    // Create middleware
    $middleware = new RoseCorsMiddleware($config);
    
    // Create pipeline
    $pipeline = new RosePipeline($app);
    $pipeline->through([$middleware]);
    
    // Create test request
    $request = Request::create('/api/users', $method);
    if ($origin) {
        $request->headers->set('Origin', $origin);
    }
    
    if ($method === 'OPTIONS') {
        $request->headers->set('Access-Control-Request-Method', 'GET');
        $request->headers->set('Access-Control-Request-Headers', 'Content-Type');
    }
    
    // Process request through middleware
    $response = $pipeline->then($request, function ($request) {
        return new Response('Test response', 200);
    });
    
    // Check results
    echo "Request: {$method} /api/users" . ($origin ? " (Origin: {$origin})" : "") . "\n";
    echo "Response Status: {$response->getStatusCode()}\n";
    echo "Response Headers:\n";
    
    $corsHeaders = array_filter(
        $response->headers->all(),
        function ($key) {
            return strpos($key[0], 'access-control') === 0;
        },
        ARRAY_FILTER_USE_KEY
    );
    
    if (empty($corsHeaders)) {
        echo "  \033[31mNo CORS headers found\033[0m\n";
    } else {
        foreach ($corsHeaders as $name => $values) {
            echo "  \033[32m{$name}\033[0m: " . implode(', ', $values) . "\n";
        }
    }
    
    echo "\n";
}

$app->handleRequest(Request::createFromGlobals());
dd($app);

// Run tests with different configurations

// Test 1: Allow all origins with default settings
testCors($app, "Default configuration with wildcard origin", [
    'allowedOrigins' => ['*'],
], 'https://example.com');

// Test 2: Allow specific origin
testCors($app, "Specific allowed origin", [
    'allowedOrigins' => ['https://example.com'],
], 'https://example.com');

// Test 3: Disallowed origin
testCors($app, "Disallowed origin", [
    'allowedOrigins' => ['https://example.com'],
], 'https://malicious-site.com');

// Test 4: Preflight request
testCors($app, "Preflight request", [
    'allowedOrigins' => ['https://example.com'],
    'allowedMethods' => ['GET', 'POST'],
    'allowedHeaders' => ['Content-Type'],
    'maxAge' => 3600,
], 'https://example.com', 'OPTIONS');

// Test 5: With credentials
testCors($app, "With credentials support", [
    'allowedOrigins' => ['https://example.com'],
    'supportsCredentials' => true,
], 'https://example.com');

echo "\033[1;32mAll tests completed\033[0m\n";
