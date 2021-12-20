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
     * This makes to {@link NodePublishIntegrityCheckService::groupChangesByDimension} available for testing
     * @param NodeInterface[] $nodesToPublish
     * @return array
     */
    public function groupChangesByDimension(array $nodesToPublish): array
    {
        return parent::groupChangesByDimension($nodesToPublish);
    }
}
