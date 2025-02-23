<?php

namespace Rose\Contracts\System;

interface FileSystem
{
    /**
     * Determine if a file or dir exists
     *
     * @param string $path
     * @return bool
     */
    public function exists($path);

    /**
     * @param string $path
     * @return string
     */
    public function get($path);

    /**
     * @param string $path
     * @param mixed $data
     * @return string
     */
    public function put($path, $data);

    /**
     * @param string $path
     * @return bool
     */
    public function delete($path);

    /**
     * @param string $path
     * @return bool
     */
    public function isDirectory($path);

    /**
     * @param string $path
     * @return mixed
     */
    public function glob($path);

    /**
     * @param string $path
     * @param int $permission
     * @param bool $recursive
     * @return mixed
     */
    public function makeDirectory($path, $permission, $recursive);

    /**
     * @param string $path
     * @param int $flags
     * @return string
     *
     * @throws \Rose\Exception\FileNotFoundException
     */
    public function json($path, $flags);

    /**
    * @param string $file
    * @return bool
    */
    public function isFile($file);


}
