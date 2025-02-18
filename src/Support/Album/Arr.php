<?php

namespace Rose\Support\Album;

class Arr
{
    /**
     * @param mixed $value
     * @return array
     */
    public static function wrap($value)
    {
        if (is_null($value))
        {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }


    /**
     * @param array $array
     * @param string|int|null $key
     * @param mixed $value
     *
     * @return mixed
     */
    public static function get($array, $key, $value = null)
    {
        if (is_null($key))
        {
            return $array;
        }

        if (str_contains($key, '.'))
        {
            return $array[$key] ?? value($value);
        }

        return $array;
    }

    /**
     * @param array $array
     * @param string|int|null $key
     * @param mixed $value
     *
     * @return mixed
     */
    public static function set($array, $key, $value = null)
    {
        if (is_null($key))
        {
            return $array = $value;
        }

        $keys = explode('.', $key);

        foreach ($keys as $i => $key) {
            if (count($keys) === 1)
            {
                break;
            }

            unset($keys[$i]);

            if (! isset($array[$key]) || ! is_array($array[$key]))
            {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;


        return $array;
    }
}
