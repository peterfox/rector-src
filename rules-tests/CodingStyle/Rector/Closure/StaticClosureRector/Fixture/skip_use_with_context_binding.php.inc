<?php

namespace Rector\Tests\CodingStyle\Rector\Closure\StaticClosureRector\Fixture;

use Rector\Tests\CodingStyle\Rector\Closure\StaticClosureRector\Source\BindObject;

BindObject::call(closure: function () {
    echo 'static call';
});

(new BindObject())->callOnObject(function () {
    echo 'method call';
});

/**
 * @param-closure-this \Rector\Tests\CodingStyle\Rector\Closure\StaticClosureRector\Source\BindObject $closure
 * @param-closure-this \Rector\Tests\CodingStyle\Rector\Closure\StaticClosureRector\Source\BindObject $closureFinally
 */
function bind_on_object(\Closure $closure = null, \Closure $anotherClosure = null,  \Closure $closureFinally = null)
{

}

bind_on_object(function () {
    echo 'closure func call 0';
});

bind_on_object(anotherClosure: function () {
    echo 'anotherClosure func call 1';
}, closureFinally: function () {
    echo 'closureFinally 1';
});

bind_on_object(closure: function () {
    echo 'closure func call 2';
}, anotherClosure: function () {
    echo 'anotherClosure func call 2';
});

?>
----
<?php

namespace Rector\Tests\CodingStyle\Rector\Closure\StaticClosureRector\Fixture;

use Rector\Tests\CodingStyle\Rector\Closure\StaticClosureRector\Source\BindObject;

BindObject::call(function () {
    echo 'static call';
});

(new BindObject())->callOnObject(function () {
    echo 'method call';
});

/**
 * @param-closure-this \Rector\Tests\CodingStyle\Rector\Closure\StaticClosureRector\Source\BindObject $closure
 * @param-closure-this \Rector\Tests\CodingStyle\Rector\Closure\StaticClosureRector\Source\BindObject $closureFinally
 */
function bind_on_object(\Closure $closure = null, \Closure $anotherClosure = null,  \Closure $closureFinally = null)
{

}

bind_on_object(function () {
    echo 'closure func call 0';
});

bind_on_object(anotherClosure: static function () {
    echo 'anotherClosure func call 1';
}, closureFinally: function () {
    echo 'closureFinally func call 1';
});

bind_on_object(closure: function () {
    echo 'closure func call 2';
}, anotherClosure: static function () {
    echo 'anotherClosure func call 2';
});

?>
