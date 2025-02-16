<?php

namespace Rose\Support\Traits;

use Closure;
use function Rose\Support\Album\data_get;

trait Enumerator
{
    /**
     *
     * @param  (callable(TValue,  $TKey):   bool) $key
     * @param  TValue|string|null $operator
     * @param  TValue|null        $value
     * @return static<int<0,1>, static<TKey, TValue>
     */
    public function partition($key, $operator = null, $value = null)
    {
        $failed = [];
        $resolved = [];

        $callback = func_num_args() === 1 ? $this->getValue($key) : $this->operatorForWhere(...func_num_args());

        foreach ($this as $key => $item) {
            if ($callback($item, $key)) {
                $resolved[$key] = $item;
            } else {
                $failed[$key] = $item;
            }
        }

        return new static([new static($resolved), new static($failed)]);
    }

    /**
     * @param callable|string $key
     * @param string|null     $operator
     * @param mixed           $value
     *
     * @return Closure
     */
    protected function operatorForWhere($key, $operator = null, $value = null)
    {
        if ($this->useAsCallable($key)) {
            return $key;
        }

        if (func_num_args() === 1) {
            $value = true;

            $operator = '=';
        }

        if (func_num_args() === 2) {
            $value = $operator;

            $operator = '=';
        }

        return function ($item) use ($key, $operator, $value) {
            $retrieved = enum_value(data_get($item, $key));
            $value = enum_value($value);

            $strings = array_filter(
                [$retrieved, $value], function ($value) {
                    return match (true) {
                        is_string($value) => true,
                        $value instanceof \Stringable => true,
                        default => false,
                    };
                }
            );

            if (count($strings) < 2 && count(array_filter([$retrieved, $value], 'is_object')) == 1) {
                return in_array($operator, ['!=', '<>', '!==']);
            }

            switch ($operator) {
            default:
            case '=':
            case '==':  
                return $retrieved == $value;
            case '!=':
            case '<>':  
                return $retrieved != $value;
            case '<':   
                return $retrieved < $value;
            case '>':   
                return $retrieved > $value;
            case '<=':  
                return $retrieved <= $value;
            case '>=':  
                return $retrieved >= $value;
            case '===': 
                return $retrieved === $value;
            case '!==': 
                return $retrieved !== $value;
            case '<=>': 
                return $retrieved <=> $value;
            }
        };

    }

    /**
     *
     * @param  callable|string|null
     * @return bool
     */
    protected function useAsCallable($key)
    {
        return ! is_string($key) && is_callable($key);
    }

    /**
     *
     * @param  callable|string|null
     * @return callable
     */
    protected function getValue($key)
    {
        if ($this->useAsCallable($key)) {
            return $key;
        }

        return fn($item) => data_get($item, $key);
    }


}
