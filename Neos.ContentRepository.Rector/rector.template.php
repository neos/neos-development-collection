<?php

declare(strict_types=1);

use Neos\ContentRepository\Rector\ContentRepositorySets;
use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->sets([
        ContentRepositorySets::CONTENTREPOSITORY_9_0
    ]);
    $rectorConfig->paths([
        __DIR__ . '/src'
    ]);
};
