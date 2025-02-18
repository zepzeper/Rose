<?php

namespace Rose\Support;

class DefaultProviders
{
    /**
    * @var array
    */
    protected $providers;

    public function __construct(?array $providers = null)
    {
        $this->providers = $providers ?: [
            \Rose\Session\SessionServiceProvider::class,
        ];
    }

    /**
    *
    * @param array $providers
    * @return static
    */
    public function merge($providers)
    {
        $this->providers = array_merge($this->providers, $providers);

        return new static($this->providers);
    }

    /**
    * @return array
    */
    public function toArray()
    {
        return $this->providers;
    }
}
