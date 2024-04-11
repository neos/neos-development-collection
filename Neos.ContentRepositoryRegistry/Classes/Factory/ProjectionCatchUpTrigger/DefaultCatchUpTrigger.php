<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Factory\ProjectionCatchUpTrigger;

use Neos\ContentRepository\Core\Projection\CatchUpOptions;
use Neos\ContentRepository\Core\Projection\ProjectionCatchUpTriggerInterface;
use Neos\ContentRepository\Core\Projection\Projections;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;

/**
 * @internal
 */
class DefaultCatchUpTrigger implements ProjectionCatchUpTriggerInterface
{

    public function __construct(
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry,
        private readonly ContentRepositoryId $contentRepositoryId,
    ) {
    }

    public function triggerCatchUp(): void
    {
        $contentRepository = $this->contentRepositoryRegistry->get($this->contentRepositoryId);
        $contentRepository->catchUpProjections();
    }
}
