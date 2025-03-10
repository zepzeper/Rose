<?php

namespace Rose\Queue;

use Closure;
use Redis;
use Rose\Contracts\Container\Container;
use Rose\Contracts\Queue\QueueDriver;
use Rose\Queue\Drivers\FileQueueDriver;
use Rose\Queue\Drivers\RedisQueueDriver;
use Rose\Support\SerializableClosure;

class QueueManager
{
    /**
     * The IoC container instance.
     *
     * @var Container
     */
    protected Container $container;
    
    /**
     * The active queue connections.
     *
     * @var array
     */
    protected array $connections = [];
    
    /**
     * The default queue connection name.
     *
     * @var string
     */
    protected string $defaultConnection;
    
    /**
     * The registered queue driver resolvers.
     *
     * @var array
     */
    protected array $resolvers = [];
    
    /**
     * The queue connection configurations.
     * 
     * @var array
     */
    protected array $config = [];
    
    /**
     * Create a new queue manager instance.
     *
     * @param  Container  $container
     * @param  array  $config
     * @return void
     */
    public function __construct(Container $container, array $config = [])
    {
        $this->container = $container;
        $this->config = $config;
        
        $this->defaultConnection = $config['default'] ?? 'file';
        
        // Register the default driver resolvers
        $this->registerResolvers();
    }
    
    /**
     * Register the default queue driver resolvers.
     *
     * @return void
     */
    protected function registerResolvers(): void
    {
        $this->addResolver('file', function ($config) {
            return new FileQueueDriver($config['path'] ?? storage_path('queue'));
        });
        
        $this->addResolver('redis', function ($config) {
            $redis = new Redis();
            $redis->connect(
                $config['host'] ?? '127.0.0.1',
                $config['port'] ?? 6379
            );
            
            if (isset($config['password']) && !empty($config['password'])) {
                $redis->auth($config['password']);
            }
            
            if (isset($config['database'])) {
                $redis->select($config['database']);
            }
            
            $keyPrefix = $config['prefix'] ?? 'queue:';
            
            return new RedisQueueDriver($redis, $keyPrefix);
        });
    }
    
    /**
     * Add a queue driver resolver.
     *
     * @param  string  $driver
     * @param  \Closure  $resolver
     * @return void
     */
    public function addResolver(string $driver, Closure $resolver): void
    {
        $this->resolvers[$driver] = $resolver;
    }
    
    /**
     * Get a queue connection instance.
     *
     * @param  string|null  $name
     * @return QueueDriver
     */
    public function connection(?string $name = null): QueueDriver
    {
        $name = $name ?: $this->defaultConnection;
        
        // If we already have a connection for this name, return it
        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }
        
        // Get the queue connection configuration
        $config = $this->getConnectionConfig($name);
        
        // Resolve the driver
        $this->connections[$name] = $this->resolve($name, $config);
        
        return $this->connections[$name];
    }
    
    /**
     * Get the configuration for a connection.
     *
     * @param  string  $name
     * @return array
     */
    protected function getConnectionConfig(string $name): array
    {
        return $this->config['connections'][$name] ?? ['driver' => 'file'];
    }
    
    /**
     * Resolve a queue connection.
     *
     * @param  string  $name
     * @param  array  $config
     * @return QueueDriver
     */
    protected function resolve(string $name, array $config): QueueDriver
    {
        $driver = $config['driver'] ?? 'file';
        
        if (!isset($this->resolvers[$driver])) {
            throw new \InvalidArgumentException("No resolver registered for [$driver] queue driver.");
        }
        
        $resolver = $this->resolvers[$driver];
        $driver = $resolver($config);
        
        // Set the connection name on the driver
        return $driver->setConnectionName($name);
    }
    
    /**
     * Push a new job onto the queue.
     *
     * @param  string  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @param  string|null  $connection
     * @return string
     */
    public function push(string $job, mixed $data = null, ?string $queue = null, ?string $connection = null): string
    {
        return $this->connection($connection)->push($job, $data, $queue ?: 'default');
    }
    
    /**
     * Push a new job onto the queue after a delay.
     *
     * @param  int  $delay
     * @param  string  $job
     * @param  mixed  $data
     * @param  string|null  $queue
     * @param  string|null  $connection
     * @return string
     */
    public function later(int $delay, string $job, mixed $data = null, ?string $queue = null, ?string $connection = null): string
    {
        return $this->connection($connection)->later($delay, $job, $data, $queue ?: 'default');
    }
    
    /**
     * Push a Closure onto the queue.
     *
     * @param  \Closure  $closure
     * @param  mixed  $data
     * @param  string|null  $queue
     * @param  string|null  $connection
     * @return string
     */
    public function pushClosure(Closure $closure, mixed $data = null, ?string $queue = null, ?string $connection = null): string
    {
        $serializable = new SerializableClosure($closure);
        
        return $this->connection($connection)->push($serializable, $data, $queue ?: 'default');
    }
    
    /**
     * Push a Closure onto the queue after a delay.
     *
     * @param  int  $delay
     * @param  \Closure  $closure
     * @param  mixed  $data
     * @param  string|null  $queue
     * @param  string|null  $connection
     * @return string
     */
    public function laterClosure(int $delay, Closure $closure, mixed $data = null, ?string $queue = null, ?string $connection = null): string
    {
        $serializable = new SerializableClosure($closure);
        
        return $this->connection($connection)->later($delay, $serializable, $data, $queue ?: 'default');
    }
    
    /**
     * Get the size of the queue.
     *
     * @param  string|null  $queue
     * @param  string|null  $connection
     * @return int
     */
    public function size(?string $queue = null, ?string $connection = null): int
    {
        return $this->connection($connection)->size($queue ?: 'default');
    }
    
    /**
     * Clear the queue.
     *
     * @param  string|null  $queue
     * @param  string|null  $connection
     * @return void
     */
    public function clear(?string $queue = null, ?string $connection = null): void
    {
        $this->connection($connection)->clear($queue ?: 'default');
    }
    
    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getDefaultConnection(): string
    {
        return $this->defaultConnection;
    }
    
    /**
     * Set the default connection name.
     *
     * @param  string  $connection
     * @return void
     */
    public function setDefaultConnection(string $connection): void
    {
        $this->defaultConnection = $connection;
    }
    
    /**
     * Create a new file queue driver.
     *
     * @param  array  $config
     * @return FileQueueDriver
     */
    public function createFileDriver(array $config): FileQueueDriver
    {
        return new FileQueueDriver($config['path'] ?? storage_path('queue'));
    }
    
    /**
     * Create a new Redis queue driver.
     *
     * @param  array  $config
     * @return RedisQueueDriver
     */
    public function createRedisDriver(array $config): RedisQueueDriver
    {
        $redis = new Redis();
        $redis->connect(
            $config['host'] ?? '127.0.0.1',
            $config['port'] ?? 6379
        );
        
        if (isset($config['password']) && !empty($config['password'])) {
            $redis->auth($config['password']);
        }
        
        if (isset($config['database'])) {
            $redis->select($config['database']);
        }
        
        $keyPrefix = $config['prefix'] ?? 'queue:';
        
        return new RedisQueueDriver($redis, $keyPrefix);
    }

    /**
     * Helper function to get the storage path.
     *
     * @param  string  $path
     * @return string
     */
    protected function storage_path(string $path = ''): string
    {
        return $this->container->make('path.storage') 
            ? $this->container->make('path.storage') . ($path ? DIRECTORY_SEPARATOR . $path : $path)
            : storage_path($path);
    }
}
