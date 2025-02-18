<?php

namespace Rose\Support;

use Dotenv\Repository\Adapter\PutenvAdapter;
use Dotenv\Repository\RepositoryBuilder;
use PhpOption\Option;

class Env
{
    protected static $repository;

    protected static $putenv = true;

    public static function get($key, $default = null)
    {
        return self::getOption($key);
    }

    public static function getRepository()
    {
        if (static::$repository === null) {
            $builder = RepositoryBuilder::createWithDefaultAdapters();

            if (static::$putenv) {
                $builder = $builder->addAdapter(PutenvAdapter::class);
            }

            static::$repository = $builder->immutable()->make();
        }

        return static::$repository;
    }

    protected static function getOption($key)
    {
        return Option::fromValue($key);
    }
}
