<?php

namespace Rose\Roots\System;

use Rose\System\FileSystem;


class PackageManifest 
{
    /**
     * @var string
     */
    protected $basePath;

    /**
     * @var string
     */
    protected $vendorPath;

    /**
     * @var string
     */
    protected $manifestPath;

    /**
     * @var array
     */
    protected $manifest = [];

    /**
     * @var bool
     */
    protected $hasManifest = false;

    /**
     * Create a new package manifest instance.
     *
     * @param FileSystem $files
     * @param string $basePath
     * @param string $manifestPath
     */
    public function __construct(
        protected FileSystem $fileSystem,
        string $basePath,
        string $manifestPath
    ) {
        $this->basePath = $basePath;
        $this->vendorPath = $basePath . '/vendor';
        $this->manifestPath = $manifestPath; 
    }

    /**
     * Get the package manifest.
     *
     * @return array
     */
    public function getManifest()
    {
        if ($this->manifest)
        {
            return $this->manifest;
        }

        if ($this->fileSystem->exists($this->manifestPath))
        {
            $this->manifest = require $this->manifestPath;
            $this->hasManifest = true;
            return $this->manifest;
        }

        $this->build();

        return $this->manifest;

    }

    /**
    * Build the manifest and write to disk
    */
    public function build()
    {
        $packages = $this->getInstalledPackages();

        $this->manifest = $this->formatPackages($packages);

        $this->writeManifest();
    }

    public function shouldRecompile()
    {
        $lock = $this->basePath . '/composer.lock';

        if (! $this->fileSystem->exists($lock) || ! $this->fileSystem->exists($this->manifest))
        {
            return true;
        }

        // Check if lock file has different timestamp then cached file.
        return filemtime($lock) > filemtime($this->manifestPath);
    }

    /**
     * Get all installed packages from composer
     *
     * @return array
     */
    protected function getInstalledPackages()
    {
        $path = $this->vendorPath . '/composer/installed.json';

        if (! $this->fileSystem->exists($path))
        {
            return [];
        }

        return $this->fileSystem->json($path, JSON_THROW_ON_ERROR);
    }

    /**
     * Format the packages for the manifest.
     *
     * @param array $packages 
     * @return array 
     */
    protected function formatPackages(array $packages)
    {
        $formated = [];

        foreach ($packages as $package)
        {
            $name = $package['name'];

            // Extra framework specific packages are handled differently.
            $config = $package['extra']['rose'];

            if (! empty($config))
            {
                $formated[$name] = [
                    'providers' => $config['providers'],
                    'aliases' => $config['aliases'],
                    'config' => $this->getConfigFiles($package),
                ];
            }
        }

        return $formated;
    }

    /**
     * Get the configuration files for the package.
     *
     * @param array $package
     * @return array
     */
    protected function getConfigFiles($package)
    {
        $configs = [];

        $basePath = $this->vendorPath . '/' . $package['name'];

        $configPath = $basePath . '/config';

        if ($this->fileSystem->exists($configPath))
        {
            $files = glob($configPath, '*.php');

            foreach ($files as $file)
            {
                $configs[basename($file, '.php')] = $file;
            }

        }

        return $configs;
    }

    /**
     * Write the manifest to the disk.
     *
     * @return void
     */
    protected function writeManifest()
    {
        if (! $this->fileSystem->exists(dirname($this->manifestPath)))
        {
           mkdir(dirname($this->manifestPath), 0755, true);
        }

        file_put_contents(
            $this->manifestPath,
            '<?php return ' . var_export($this->manifest, true) . ';'
        );
    }

}
