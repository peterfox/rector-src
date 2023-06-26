<?php

namespace Rector\Tests\Transform\Rector\Param\AddSensitiveParameterAttributeRector\Fixture;

class ApplyAttributeToMethods
{
    public function login($username, $password): void
    {
    }
}

?>
-----
<?php

namespace Rector\Tests\Transform\Rector\Param\AddSensitiveParameterAttributeRector\Fixture;

class ApplyAttributeToMethods
{
    public function login($username, #[\SensitiveParameter]
    $password): void
    {
    }
}

?>
