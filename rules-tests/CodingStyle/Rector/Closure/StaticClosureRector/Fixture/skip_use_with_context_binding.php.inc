<?php

namespace Rector\Tests\CodingStyle\Rector\Closure\StaticClosureRector\Fixture;

use Rector\Tests\CodingStyle\Rector\Closure\StaticClosureRector\Source\BindObject;

BindObject::call(function () {
    echo 'foobar';
});

(new BindObject())->callOnObject(function () {
    echo 'foobar';
});

/**
 * @param-closure-this \Rector\Tests\CodingStyle\Rector\Closure\StaticClosureRector\Source\BindObject $closure
 */
function bind_on_object($closure)
{

}

bind_on_object(function () {
    echo 'foobar';
});
