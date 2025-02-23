<?php

namespace Rose\Benchmarks;

class PackageDiscoveryBenchmark
{
    protected $files;
    protected $basePath;
    protected $manifest;

    public function __construct()
    {
        $this->files = new \Rose\System\FileSystem();
        $this->basePath = dirname(__DIR__);
        $this->manifest = new \Rose\Roots\System\PackageManifest(
            $this->files,
            $this->basePath,
            $this->basePath . '/cache'
        );
    }

    public function runBenchmark($iterations = 1000)
    {
        // First, let's test uncached performance
        $uncachedTimes = [];
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            
            // Simulate uncached package discovery
            $this->discoverPackagesUncached();
            
            $end = microtime(true);
            $uncachedTimes[] = ($end - $start) * 1000; // Convert to milliseconds
        }

        // Now test cached performance
        $cachedTimes = [];
        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            
            // Use cached manifest
            $packages = $this->manifest->getManifest();
            
            $end = microtime(true);
            $cachedTimes[] = ($end - $start) * 1000; // Convert to milliseconds
        }

        return $this->generateReport($uncachedTimes, $cachedTimes);
    }

    protected function discoverPackagesUncached()
    {
        $packages = [];
        $vendorPath = $this->basePath . '/vendor';
        
        if (!$this->files->exists($vendorPath)) {
            return [];
        }

        // Simulate full package discovery without cache
        $composerFiles = glob($vendorPath . '/*/*/composer.json');
        foreach ($composerFiles as $file) {
            if ($this->files->exists($file)) {
                $config = $this->files->json($file, JSON_THROW_ON_ERROR);
                // Process package config similar to PackageManifest
                if (isset($config['extra']['rose'])) {
                    $packages[dirname($file)] = $config['extra']['rose'];
                }
            }
        }

        return $packages;
    }

    protected function generateReport($uncachedTimes, $cachedTimes)
    {
        $report = [
            'uncached' => [
                'avg' => array_sum($uncachedTimes) / count($uncachedTimes),
                'min' => min($uncachedTimes),
                'max' => max($uncachedTimes),
            ],
            'cached' => [
                'avg' => array_sum($cachedTimes) / count($cachedTimes),
                'min' => min($cachedTimes),
                'max' => max($cachedTimes),
            ],
        ];

        $report['improvement'] = [
            'percent' => (($report['uncached']['avg'] - $report['cached']['avg']) / $report['uncached']['avg']) * 100,
            'factor' => $report['uncached']['avg'] / $report['cached']['avg']
        ];

        return $report;
    }
}


