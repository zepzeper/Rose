<?php

namespace Rose\Support\Album;

use ArgumentCountError;
use ArrayAccess;
use Rose\Support\Traits\Enumerator;

class Arr
{
    /**
     *
     * @param ArrayAccess|array $value
     *
     * @return bool
     */
    public static function accessible($value)
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }


    /**
     *
     * @param Enumerator|ArrayAccess|array $array
     * @param string|int|float             $key
     *
     * @return bool
     */
    public static function exists($array, $key)
    {
        if ($array instanceof Enumerator) {
            return $array->has($key);
        }

        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }

        return array_key_exists($key, $array);
    }

    /**
     * @param  mixed $value
     * @return array
     */
    public static function wrap($value)
    {
        if (is_null($value)) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }

    public static function map($array, $callback)
    {
        $keys = array_keys($array);

        try {
            $items = array_map($callback, $array, $keys);
        } catch (ArgumentCountError) {
            $items = array_map($callback, $array);
        }

        return array_combine($keys, $items);
    }

    /**
     * Flatten array of arrays to a single array
     *
     * @param  iterable $array
     * @return array
     */
    public static function flatten($array)
    {
        $results = [];

        foreach ($array as $values) {
            if ($values instanceof Collection) {
                $values = $values->all();
            }

            if (is_array($values)) {
                $results = array_merge($results, $values);
            } else {
                // Add abstract values directly to the results
                $results[] = $values;
            }
        }

        return $results;
    }

    /**
     * @param array           $array
     * @param string|int|null $key
     * @param mixed           $value
     *
     * @return mixed
     */
    public static function get($array, $key, $default = null)
    {
        if (! static::accessible($array)) {
            return value($default);
        }

        if (is_null($key)) {
            return $array;
        }

        if (static::exists($array, $key)) {
            return $array[$key];
        }

        if (! str_contains($key, '.')) {
            return $array[$key] ?? value($default);
        }

        foreach (explode('.', $key) as $segment) {
            if (static::accessible($array) && static::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return value($default);
            }
        }

        return $array;
    }

    /**
     * @param array           $array
     * @param string|int|null $key
     * @param mixed           $value
     *
     * @return mixed
     */
    public static function set(&$array, $key, $value = null)
    {
        if (is_null($key)) {
            return $array = $value;
        }

        $keys = explode('.', $key);

        foreach ($keys as $i => $key) {
            if (count($keys) === 1) {
                break;
            }

            unset($keys[$i]);

            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }
}
