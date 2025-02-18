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
        if ($this->isFile($path))
        {
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
}
