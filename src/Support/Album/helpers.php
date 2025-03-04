<?php

use Rose\Container\Container;
use Rose\Roots\Http\Helpers\HtmxHelper;
use Rose\Support\Album\Arr;
use Rose\Support\Album\Collection;

if (! function_exists('data_get')) {
    /**
     * Get an item from an array or object using "dot" notation.
     *
     * @param  mixed                 $target
     * @param  string|array|int|null $key
     * @param  mixed                 $default
     * @return mixed
     */
    function data_get($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        foreach ($key as $i => $segment) {
            unset($key[$i]);

            if (is_null($segment)) {
                return $target;
            }

            if ($segment === '*') {
                if ($target instanceof Collection) {
                    $target = $target->all();
                } elseif (! is_iterable($target)) {
                    return value($default);
                }

                $result = [];

                foreach ($target as $item) {
                    $result[] = data_get($item, $key);
                }

                return in_array('*', $key) ? Arr::flatten($result) : $result;
            }

            $segment = match ($segment) {
                '\*' => '*',
                '\{first}' => '{first}',
                '{first}' => array_key_first(is_array($target) ? $target : (new Collection($target))->all()),
                '\{last}' => '{last}',
                '{last}' => array_key_last(is_array($target) ? $target : (new Collection($target))->all()),
                default => $segment,
            };

            if (Arr::accessible($target) && Arr::exists($target, $segment)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return value($default);
            }
        }

        return $target;
    }
}

if (! function_exists('tap')) {
    /**
     * Call the given Closure with the given value then return the value.
     */
    function tap($value, $callback = null)
    {
        if (is_null($callback)) {
            return new Exception($value);
        }

        $callback($value);

        return $value;
    }
}

if (! function_exists('storage_path')) {
    function storage_path($path)
    {
        return app()->storagePath($path);
    }
}


if (! function_exists('enum_value')) {
    function enum_value($value, $default = null)
    {
        return $value ?? value($default);
    }
}

if (! function_exists('app')) {
    function app($abstract = null, array $parameters = [])
    {
        if (is_null($abstract)) {
            return Container::getInstance();
        }

        return Container::getInstance()->make($abstract, $parameters);
    }
}

if (! function_exists('value')) {

    /**
     * Return the default value of the given value.
     *
     * @template TValue
     * @template TArgs
     *
     * @param  TValue|\Closure(TArgs): TValue  $value
     * @param  TArgs  ...$args
     * @return TValue
     */
    function value($value, ...$args)
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}

if (! function_exists('env')) {
    function env($key, $default = null)
    {
        return app()->make('env.repository')->get($key) ?? value($default);
    }
}

/**
 * Global helper function to configure HTMX with CSRF protection
 *
 * @return string
 */
if (!function_exists('htmx_csrf_setup')) {
    function htmx_csrf_setup(): string
    {
        return app(HtmxHelper::class)->setup();
    }
}

/**
 * Global helper function to get a CSRF field for forms
 *
 * @return string
 */
if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return app(HtmxHelper::class)->csrfField();
    }
}

/**
 * Global helper function to get a CSRF token
 *
 * @return string
 */
if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return app(HtmxHelper::class)->getCsrfToken();
    }
}
