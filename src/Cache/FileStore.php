<?php

namespace Rose\Cache;

use Rose\Contracts\Cache\Store;
use Rose\System\FileSystem;

class FileStore implements Store
{
    protected $directory;

    public function __construct(protected Filesystem $files, $directory)
    {
        $this->directory = $directory;
    }


    /**
     * Get an item from the cache.
     *
     * @param string $key
     * @return array|null
     */
    public function get($key)
    {
        $path = $this->path($key);

        if (! $this->files->exists($path))
        {
            return null;
        }

        try {
            $contents = $this->files->get($path);

            $data = unserialize($contents);

            if (isset($data['expiration']) && time() >= $data['expiration'])
            {
                $this->forget($key);
                return null;
            }

            return $data['value'];

        } catch (\Exception $e)
        {
            return null;
        }
    }

    /**
     * Store an item in the cache.
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return bool
     */
    public function put($key, $value, $ttl = null)
    {
        $path = $this->path($key);

        $data = serialize([
            'value' => $value,
            'expiration' => $ttl
        ]);

        $this->ensureCacheDirectoryExists();

        return $this->files->put($path, $data) !== false;
    }

    /**
     * Remove an item from the cache.
     *
     * @param string $key
     * @return bool
     */
    public function forget($key)
    {
        $path = $this->path($key);

        if ($this->files->exists($path))
        {
            return $this->files->delete($path);
        }
        return false;

    }

    /**
     * Check if item exists in cache.
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        $path = $this->path($key);

        if (!$this->files->exists($path)) {
            return false;
        }

        try {
            $contents = $this->files->get($path);
            $data = unserialize($contents);
            
            // Check expiration
            if (isset($data['expiration']) && time() >= $data['expiration']) {
                $this->forget($key);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Clear all items from the cache.
     *
     * @return bool
     */
    public function flush()
    {
        if (!$this->files->isDirectory($this->directory)) {
            return false;
        }

        foreach ($this->files->glob($this->directory.'/*') as $file) {
            if ($this->files->isFile($file)) {
                $this->files->delete($file);
            }
        }

        return true;
    }

    /**
     * Get the full path for the given cache key.
     *
     * @param string $key
     * @return string
     */
    protected function path($key)
    {
        $hash = sha1($key);
        return $this->directory . '/' . $hash . '.cache';
    }

    /**
     * Ensure the cache directory exists.
     *
     * @return void
     */
    protected function ensureCacheDirectoryExists()
    {
        if (!$this->files->exists($this->directory)) {
            $this->files->makeDirectory($this->directory, 0755, true);
        }
    }
}
