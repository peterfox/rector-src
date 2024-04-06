<?php

namespace Rector\Tests\CodingStyle\Rector\Closure\StaticClosureRector\Fixture;

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

bind_on_object(anotherClosure: static function () {
    echo 'anotherClosure func call 1';
}, closureFinally: function () {
    echo 'closureFinally 1';
});

bind_on_object(closure: function () {
    echo 'closure func call 2';
}, anotherClosure: static function () {
    echo 'anotherClosure func call 2';
});

?>
