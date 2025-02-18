<?php

namespace Rose\Support;

use Rose\Contracts\Container\Container;

abstract class Manager
{
    protected Container $container;

    protected $config;

    protected array $drivers = [];

    public function __construct(Container $container)
    {
        $this->container = $container;

        /*$this->config = $container->make('config');*/
    }

    abstract public function getDefaultDriver();

    public function driver($driver = null)
    {

        return $this->drivers[$driver];
    }

    public function getDrivers()
    {
        return $this->drivers;
    }

    public function getContainer()
    {
        return $this->container;
    }

    public function setContainer(Container $container)
    {
        $this->container = $container;
    }

    public function flushDrivers()
    {
        $this->drivers = [];

        return $this;
    }

    public function __call($method, $params)
    {
        return $this->driver()->$method(...$params);
    }
}
