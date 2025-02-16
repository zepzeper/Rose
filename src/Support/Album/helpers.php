<?php

namespace Rose\Support\Album;

if (! function_exists('get_data')) {
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

                return in_array('*', $key) ? Arr::collapse($result) : $result;
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
