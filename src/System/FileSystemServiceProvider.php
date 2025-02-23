<?php

namespace Rose\System;

use Rose\System\FileSystem;
use Rose\Support\ServiceProvider;

class FileSystemServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('files', function () {
            return new FileSystem;
        });
    }

}
