<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Service;

use Neos\Fusion\Core\FusionSourceCodeCollection;

/**
 * @internal
 */
interface FusionAutoIncludeHandler
{
    public function loadFusionFromPackage(string $packageKey, FusionSourceCodeCollection $sourceCodeCollection): FusionSourceCodeCollection;
}
