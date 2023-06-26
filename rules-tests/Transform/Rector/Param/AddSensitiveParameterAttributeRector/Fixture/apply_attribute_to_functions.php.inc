<?php

namespace Rector\Tests\Transform\Rector\Param\AddSensitiveParameterAttributeRector\Fixture;

function login($username, $password): void
{
}

?>
-----
<?php

namespace Rector\Tests\Transform\Rector\Param\AddSensitiveParameterAttributeRector\Fixture;

function login($username, #[\SensitiveParameter]
$password): void
{
}

?>
