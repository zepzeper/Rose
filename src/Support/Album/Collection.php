<?php

namespace Rose\Support\Album;

use InvalidArgumentException;
use JsonSerializable;
use Rose\Support\Traits\Enumerator;
use Traversable;
use UnitEnum;
use WeakMap;

class Collection
{
    use Enumerator;

    /**
    * The items in the collection
    *
    * @var array<TKey, TValue>
    */
    protected $items = [];

    /**
     * @param mixed $items
     */
    public function __construct($items = [])
    {
        $this->items = $this->getArrayableItems($items);
    }

    /**
     * @return array<TKey,TValue>
     */
    public function all()
    {
        return $this->items;
    }

    /**
     * @return Collection
     */
    public function flatten()
    {
        return new static(Arr::flatten($this->items));
    }

    /**
     * @return Collection
     */
    public function map(callable $callback)
    {
        return new static(Arr::map($this->items, $callback));
    }

    /**
     * @param mixed $items
     * @return array<TKey, TValue>
     */
    protected function getArrayableItems($items)
    {
        if (is_array($items)) {
            return $items;
        }

        return match (true) {
            $items instanceof WeakMap => throw new InvalidArgumentException("Collections can not be created using a WeakMap"),
            $items instanceof Traversable => iterator_to_array($items),
            $items instanceof JsonSerializable => (array) $items->jsonSerialize(),
            $items instanceof UnitEnum => [$items],
            default => (array) $items
        };
    }


}
