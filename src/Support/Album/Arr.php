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
     * @param string|int|float  $key
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

        return array_key_exists($array, $key);
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
     * @param  array $array
     * @return array
     */
    public static function flatten($array)
    {
        $results = [];

        foreach ($array as $values) {
            if ($values instanceof Collection) {
                $values = $values->all();
            } else if (! is_array($values)) {
                continue;
            }

            $results[] = $values;
        }

        return array_merge([], ...$results);
    }

    /**
     * @param array           $array
     * @param string|int|null $key
     * @param mixed           $value
     *
     * @return mixed
     */
    public static function get($array, $key, $value = null)
    {
        if (is_null($key)) {
            return $array;
        }

        if (str_contains($key, '.')) {
            return $array[$key] ?? value($value);
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
