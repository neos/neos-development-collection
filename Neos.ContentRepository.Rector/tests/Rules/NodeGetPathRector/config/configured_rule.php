<?php

declare (strict_types=1);
//namespace RectorPrefix202208;

use Neos\ContentRepository\Rector\Rules\NodeGetPathRector;
use Rector\Config\RectorConfig;
return static function (RectorConfig $rectorConfig) : void {
    $rectorConfig->rule(NodeGetPathRector::class);
};
