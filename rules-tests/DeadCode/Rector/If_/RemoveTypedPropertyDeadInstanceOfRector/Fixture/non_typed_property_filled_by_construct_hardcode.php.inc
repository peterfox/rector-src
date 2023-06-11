<?php

namespace Rector\Tests\DeadCode\Rector\If_\RemoveTypedPropertyDeadInstanceOfRector\Fixture;

use stdClass;

class NonTypedPropertyFilledByConstructHardCode
{
    private $var;

    public function __construct()
    {
        $this->var = new stdClass;
    }

    public function go()
    {
        if (! $this->var instanceof stdClass) {
            echo 'var is not an stdClass';
            return;
        }

        echo 'success' . PHP_EOL;
    }
}

?>
-----
<?php

namespace Rector\Tests\DeadCode\Rector\If_\RemoveTypedPropertyDeadInstanceOfRector\Fixture;

use stdClass;

class NonTypedPropertyFilledByConstructHardCode
{
    private $var;

    public function __construct()
    {
        $this->var = new stdClass;
    }

    public function go()
    {
        echo 'success' . PHP_EOL;
    }
}

?>