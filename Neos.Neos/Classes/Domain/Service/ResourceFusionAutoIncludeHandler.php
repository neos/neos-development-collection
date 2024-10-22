<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\FusionSourceCodeCollection;

/**
 * @internal
 * @Flow\Scope("singleton")
 */
class ResourceFusionAutoIncludeHandler implements FusionAutoIncludeHandler
{
    public function loadFusionFromPackage(string $packageKey, FusionSourceCodeCollection $sourceCodeCollection): FusionSourceCodeCollection
    {
        return $sourceCodeCollection->union(
            FusionSourceCodeCollection::tryFromFilePath(sprintf('resource://%s/Private/Fusion/Root.fusion', $packageKey))
        );
    }
}
