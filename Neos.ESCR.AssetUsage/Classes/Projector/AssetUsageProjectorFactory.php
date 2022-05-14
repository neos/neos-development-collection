<?php
declare(strict_types=1);

namespace Neos\ESCR\AssetUsage\Projector;

use Neos\Flow\Annotations as Flow;

// NOTE: as workaround, we cannot reflect this class (because of an overly eager DefaultEventToListenerMappingProvider in
// Neos.EventSourcing - which will be refactored soon). That's why we need an extra factory for AssetUsageProjector.
// See Neos.ContentRepositoryRegistry/Configuration/Settings.hacks.yaml for further details.

/**
 * @Flow\Scope("singleton")
 */
final class AssetUsageProjectorFactory
{

    public function __construct(
        private readonly AssetUsageRepository $repository
    ) {
    }

    public function build(): AssetUsageProjector
    {
        return new AssetUsageProjector($this->repository);
    }
}
