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
    protected ResourceFusionAutoIncludeHandler $defaultHandler;

    /**
     * @var array<string,FusionSourceCodeCollection|true>
     */
    private array $overriddenIncludes = [];

    public function setIncludeFusionPackage(string $packageKey): void
    {
        $this->overriddenIncludes[$packageKey] = true;
    }

    public function setFusionForPackage(string $packageKey, FusionSourceCodeCollection $packageFusionSource): void
    {
        $this->overriddenIncludes[$packageKey] = $packageFusionSource;
    }

    public function reset(): void
    {
        $this->overriddenIncludes = [];
    }

    /**
     * If no override is set via {@see setIncludeFusionPackage} or {@see setFusionForPackage} we load all the fusion via the default implementation
     */
    public function loadFusionFromPackage(string $packageKey, FusionSourceCodeCollection $sourceCodeCollection): FusionSourceCodeCollection
    {
        if ($this->overriddenIncludes === []) {
            return $this->defaultHandler->loadFusionFromPackage($packageKey, $sourceCodeCollection);
        }
        $override = $this->overriddenIncludes[$packageKey] ?? null;
        if ($override === null) {
            return $sourceCodeCollection;
        }
        if ($override === true) {
            return $this->defaultHandler->loadFusionFromPackage($packageKey, $sourceCodeCollection);
        }
        return $sourceCodeCollection->union($override);
    }
}
