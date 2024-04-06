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
 * @param-closure-this \Rector\Tests\CodingStyle\Rector\Closure\StaticClosureRector\Source\BindObject $closureFinally
 */
function bind_on_object(\Closure $closure = null, \Closure $anotherClosure = null,  \Closure $closureFinally = null)
{

}

bind_on_object(anotherClosure: function () {
    echo 'foobar';
}, closureFinally: function () {
    echo 'foobar';
});

bind_on_object(closure: function () {
    echo 'foobar';
}, anotherClosure: function () {
    echo 'foobar';
});

?>
----
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
 * @param-closure-this \Rector\Tests\CodingStyle\Rector\Closure\StaticClosureRector\Source\BindObject $closureFinally
 */
function bind_on_object(\Closure $closure = null, \Closure $anotherClosure = null,  \Closure $closureFinally = null)
{

}

bind_on_object(anotherClosure: static function () {
    echo 'foobar';
}, closureFinally: function () {
    echo 'foobar';
});

bind_on_object(closure: function () {
    echo 'foobar';
}, anotherClosure: static function () {
    echo 'foobar';
});

?>
