<?php

namespace Rector\Tests\Transform\Rector\Param\AddSensitiveParameterAttributeRector\Fixture;

class SkipSensitiveParameterAttributeExists
{
    public function login($username, #[\SensitiveParameter]
    $password): void
    {
    }
}

?>
