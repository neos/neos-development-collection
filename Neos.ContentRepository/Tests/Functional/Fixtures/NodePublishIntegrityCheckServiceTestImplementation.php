<?php

namespace Neos\ContentRepository\Tests\Functional\Fixtures;

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Service\NodePublishIntegrityCheckService;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 * @internal
 */
class NodePublishIntegrityCheckServiceTestImplementation extends NodePublishIntegrityCheckService
{
    /**
     * This makes to {@link NodePublishIntegrityCheckService::groupChangesByEffectedDimensionAndPreset} available for testing
     * @param NodeInterface[] $nodesToPublish
     * @return array
     */
    public function groupChangesByEffectedDimensionAndPreset(array $nodesToPublish): array
    {
        return parent::groupChangesByEffectedDimensionAndPreset($nodesToPublish);
    }
}
