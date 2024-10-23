<?php

declare(strict_types=1);

namespace Neos\Neos\Testing;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\FusionSourceCodeCollection;
use Neos\Neos\Domain\Service\FusionAutoIncludeHandler;
use Neos\Neos\Domain\Service\ResourceFusionAutoIncludeHandler;

/**
 * @internal only for testing purposes of the Neos.Neos package
 * @Flow\Scope("singleton")
 */
class TestingFusionAutoIncludeHandler implements FusionAutoIncludeHandler
{
    /**
     * @Flow\Inject
     */
    protected ResourceFusionAutoIncludeHandler $resourceFusionAutoIncludeHandler;

    private ?FusionAutoIncludeHandler $overrideHandler = null;

    public function overrideHandler(FusionAutoIncludeHandler $overrideHandler): void
    {
        $this->overrideHandler = $overrideHandler;
    }

    public function resetOverride(): void
    {
        $this->overrideHandler = null;
    }

    public function loadFusionFromPackage(string $packageKey, FusionSourceCodeCollection $sourceCodeCollection): FusionSourceCodeCollection
    {
        if ($this->overrideHandler !== null) {
            return $this->overrideHandler->loadFusionFromPackage($packageKey, $sourceCodeCollection);
        } else {
            return $this->resourceFusionAutoIncludeHandler->loadFusionFromPackage($packageKey, $sourceCodeCollection);
        }
    }
}
