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

}
