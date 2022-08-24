<?php

declare (strict_types=1);
//namespace RectorPrefix202208;

use Neos\ContentRepository\Rector\Legacy\LegacyContextStub;
use Neos\ContentRepository\Rector\Rules\ContextFactoryToLegacyContextStubRector;
use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\Name\RenameClassRector;

return static function (RectorConfig $rectorConfig) : void {
    $rectorConfig->rule(ContextFactoryToLegacyContextStubRector::class);
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Neos\ContentRepository\Domain\Service\Context' => LegacyContextStub::class,
        'Neos\Neos\Domain\Service\ContentContext' => LegacyContextStub::class,
    ]);
};
