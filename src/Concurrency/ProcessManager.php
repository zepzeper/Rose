<?php

namespace Rose\Concurrency;

use Rose\Contracts\Concurrency\Pool as PoolContract;
use Rose\Contracts\Container\Container;

class ProcessManager
{
    /**
     * @var Container
     */
    protected Container $container;
    
    /**
     * Create a new process manager instance.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    
    /**
     * Create a new pool with the given concurrency.
     *
     * @param int $concurrency
     * @return PoolContract
     */
    public function pool(int $concurrency = 5): PoolContract
    {
        return new Pool($concurrency);
    }
    
    /**
     * Create a new async pool with the given concurrency.
     *
     * @param int $concurrency
     * @return PoolContract
     */
    public function async(int $concurrency = 5): PoolContract
    {
        return Pool::async($concurrency);
    }
    
    /**
     * Create a new parallel pool with the given concurrency.
     *
     * @param int $concurrency
     * @return PoolContract
     */
    public function parallel(int $concurrency = 5): PoolContract
    {
        return Pool::parallel($concurrency);
    }
}
