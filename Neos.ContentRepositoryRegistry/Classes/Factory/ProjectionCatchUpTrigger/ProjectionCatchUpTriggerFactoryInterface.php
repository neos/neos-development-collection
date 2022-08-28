<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\ProjectionCatchUpTrigger;

use Neos\ContentRepository\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Projection\ProjectionCatchUpTriggerInterface;
use Neos\ContentRepository\Factory\ContentRepositoryIdentifier;

interface ProjectionCatchUpTriggerFactoryInterface
{
    public function build(ContentRepositoryIdentifier $contentRepositoryIdentifier, array $contentRepositorySettings, array $projectionCatchUpTriggerPreset): ProjectionCatchUpTriggerInterface;
}
