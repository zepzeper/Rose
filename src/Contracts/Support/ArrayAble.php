<?php

namespace Rose\Contracts\Support;

/*
 * @template Tkey of array-key
 * @template Tvalue
 *
 */
interface ArrayAble
{
    /*
    *
    * @return array<Tkey, Tvalue>
    */
    public function toArray();
}
