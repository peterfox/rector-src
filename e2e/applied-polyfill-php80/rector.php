<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Identical\StrStartsWithRector;
use Rector\ValueObject\PhpVersion;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
    ]);

    $rectorConfig->phpVersion(PhpVersion::PHP_74);
    $rectorConfig->rule(StrStartsWithRector::class);
};
