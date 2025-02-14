<?php

namespace Rose\Roots;

class Application
{

    protected bool $booted = false;
    protected string $basePath;

    /**
     * The classes it uses to bootsrap the application.
     *
     * @var string[]
     */
    protected $bootstapClasses = [
        \Rose\Roots\Bootstrap\BootProvider::class
    ];

    /**
     * Create a new Rose application instance.
     *
     * @param  string|null  $basePath
     * @return void
     */
    public function __construct($basePath = null)
    {
        if ($basePath) {
            //$this->setBasePath($basePath);
        }
    }

    /**
     * Boot the App and Services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->isBooted()) {
            return;
        }
        
        $this->booted = true;
    }

    /**
     * Check wether the App is already booted or not
     * 
     * @return bool
     */
    private function isBooted()
    {
        return $this->booted;
    }

    public function getInstance($type, $request)
    {


    }

    public function setBasePath($basePath)
    {
        $this->basePath = rtrim($basePath, '\/');

        return $this;
    }

}
