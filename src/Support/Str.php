<?php

namespace Rose\Support;

class Str
{
    /**
     * @param string $haystack
     * @param iterable<string>|string $needles
     * @return bool
     */
    public static function startsWith($haystack, $needles): bool
    {
        if (! is_iterable($needles))
        {
            $needles = [$needles];
        }

        if (is_null($haystack))
        {
            return false;
        }

        foreach ($needles as $needle) {
            if ($needle !== '' && str_starts_with($haystack, $needle))
            {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $haystack
     * @param string $needle
     * @return string|bool
     */
    public static function remainder($haystack, $needle): string|bool
    {
        return $needle === '' ? $haystack : array_reverse(explode($needle, $haystack, 2))[0];
    }
}
