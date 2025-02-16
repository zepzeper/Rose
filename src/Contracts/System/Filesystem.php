<?php

namespace Rose\Contracts\System;

interface Filesystem
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
