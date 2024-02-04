<?php

namespace Rector\Tests\Renaming\Rector\FunctionLike\RenameFunctionLikeParamWithinCallLikeParamRector\Fixture;

use SomeNamespace\SomeClass;
use SomeNamespace\SomeClassForNamed;

SomeClass::someCall(function ($qry, $query) {
    echo $query;
    return $qry;
});

SomeClass::someCall(function ($qry, $query) {
    return $qry;
});

$query = 'a';

SomeClass::someCall(function ($qry) use ($query) {
    return $query;
});

SomeClass::someCall(function ($qry) use ($query) {
    return $qry;
});

SomeClass::someCall(fn ($qry) => $query);

SomeClass::someCall(function ($qry) {
    echo $qry;
    return function ($query) use ($qry) {
        return $query;
    };
});

?>