<?php

namespace Rose\System;

use Rose\Contracts\System\FileSystem as FileSystemContract;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class FileSystem implements FileSystemContract
{
    /**
     * Determine if a file or dir exists
     *
     * @param string $path
     * @return bool
     */
    public function exists($path)
    {
        return file_exists($path);
    }

    /**
     * @param string $path
     * @return string
     *
     * @throws \Rose\Exception\FileNotFoundException
     */
    public function get($path)
    {
        if ($this->isFile($path)) {
            return file_get_contents($path);
        }

        throw new FileNotFoundException("File does not exist in path {$path}.");
    }

    /**
     * @param string $path
     * @param int $flags
     * @return string
     *
     * @throws \Rose\Exception\FileNotFoundException
     */
    public function json($path, $flags)
    {
        return json_decode($this->get($path), true, 512, $flags);
    }

    /**
    * @param string $file
    * @return bool
    */
    public function isFile($file)
    {
        return is_file($file);
    }

    /**
     * Put content to a file.
     *
     * @param string $path
     * @param mixed $data
     * @return bool
     */
    public function put($path, $data)
    {
        if (! $this->exists($path))
        {
            $this->makeDirectory($path, 0755, true);
        }
        

        return file_put_contents($path, $data) !== false;
    }

    /**
     * Delete a file.
     *
     * @param string $path
     * @return bool
     */
    public function delete($path)
    {
        if ($this->exists($path)) {
            return unlink($path);
        }

        return false;
    }

    /**
     * Determine if the given path is a directory.
     *
     * @param string $path
     * @return bool
     */
    public function isDirectory($path)
    {
        return is_dir($path);
    }

    /**
     * Find path names matching a pattern.
     *
     * @param string $path
     * @return array
     */
    public function glob($path)
    {
        return glob($path);
    }

    /**
     * Create a directory.
     *
     * @param string $path
     * @param int $permission
     * @param bool $recursive
     * @return bool
     */
    public function makeDirectory($path, $permission = 0755, $recursive = false)
    {
        if ($this->isDirectory($path)) {
            return true;
        }

        return mkdir($path, $permission, $recursive);
    }
}
