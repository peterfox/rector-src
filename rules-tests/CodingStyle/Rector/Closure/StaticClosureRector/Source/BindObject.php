<?php

namespace Rector\Tests\CodingStyle\Rector\Closure\StaticClosureRector\Source;

class BindObject
{
    /**
     * @param-closure-this \Rector\Tests\CodingStyle\Rector\Closure\StaticClosureRector\Source\BindObject $closure
     */
    public static function call(\Closure $closure)
    {

    }

    /**
     * @param-closure-this \Rector\Tests\CodingStyle\Rector\Closure\StaticClosureRector\Source\BindObject $closure
     */
    public function callOnObject(\Closure $closure)
    {

    }
}
